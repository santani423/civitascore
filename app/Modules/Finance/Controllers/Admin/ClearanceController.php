<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\BlockExemptionStatus;
use Modules\Academic\Models\AcademicBlock;
use Modules\Academic\Models\AcademicBlockExemption;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\Academic\Services\AcademicBlockService;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\ClearanceRequirement;
use Modules\Finance\Models\FinanceClearanceRule;
use Modules\Finance\Services\ClearanceService;
use Modules\Finance\Support\Money;
use Modules\Finance\Support\Presenter;

/** Cekal keuangan & dispensasi (F-14) — penegakan oleh SIAKAD (A.8). */
class ClearanceController extends KeuController
{
    public function __construct(private readonly ClearanceService $clearance, private readonly AcademicBlockService $blocks) {}

    public function rules(): JsonResponse
    {
        return ApiResponse::success(FinanceClearanceRule::query()->with('studyProgram')->orderBy('code')->get()->map(fn (FinanceClearanceRule $r) => [
            ...$r->only(['id', 'code', 'name', 'services', 'bill_groups', 'terms', 'grace_days', 'include_penalties', 'study_program_id', 'is_active']),
            'requirement' => $r->requirement->value, 'requirement_label' => $r->requirement->label(), 'min_outstanding_amount' => (string) $r->min_outstanding_amount,
            'active_from' => $r->active_from?->toDateString(), 'active_until' => $r->active_until?->toDateString(), 'study_program' => $r->studyProgram?->name,
        ]));
    }

    public function storeRule(Request $request): JsonResponse
    {
        return ApiResponse::success(FinanceClearanceRule::query()->create([...$this->validateRule($request), 'created_by' => $this->user($request)->id]), 'Aturan cekal dibuat. Jalankan simulasi sebelum mengaktifkan.', status: 201);
    }

    public function updateRule(Request $request, FinanceClearanceRule $financeClearanceRule, AuditLogService $audit): JsonResponse
    {
        $before = $financeClearanceRule->toArray();
        $financeClearanceRule->update($this->validateRule($request, $financeClearanceRule));
        $audit->record($financeClearanceRule, AuditAction::Updated, $before, $financeClearanceRule->fresh()->toArray());

        return ApiResponse::success($financeClearanceRule->fresh(), 'Aturan cekal diperbarui. Evaluasi ulang untuk menerapkan.');
    }

    /** Simulasi (dry run, FR-CKL-02): siapa yang akan tercekal bila aturan aktif, tanpa menerbitkan cekal. */
    public function simulate(FinanceClearanceRule $financeClearanceRule): JsonResponse
    {
        $today = CarbonImmutable::today();
        $term = AcademicTerm::query()->where('is_current', true)->first();
        $affected = [];
        $students = Student::query()->where('status', 'active')
            ->when($financeClearanceRule->study_program_id, fn ($q) => $q->where('study_program_id', $financeClearanceRule->study_program_id))
            ->with('studyProgram')->get();

        foreach ($students as $student) {
            [$shortfall] = $this->clearance->shortfall($student, $financeClearanceRule, $today, $term);

            if ($shortfall > 0 && $shortfall >= Money::toCents($financeClearanceRule->min_outstanding_amount)) {
                $affected[] = ['student' => Presenter::student($student), 'shortfall' => Money::fromCents($shortfall)];
            }
        }

        return ApiResponse::success(['rule' => $financeClearanceRule->code, 'students_evaluated' => $students->count(), 'would_block' => count($affected), 'students' => array_slice($affected, 0, 500)]);
    }

    public function evaluate(Request $request): JsonResponse
    {
        $data = $request->validate(['student_id' => ['nullable', 'string']]);

        if (! empty($data['student_id'])) {
            return ApiResponse::success($this->clearance->evaluateStudent(Student::query()->findOrFail($data['student_id'])), 'Cekal mahasiswa dievaluasi ulang.');
        }

        return ApiResponse::success(['evaluated' => $this->clearance->evaluateAll()], 'Seluruh mahasiswa aktif dievaluasi ulang.');
    }

    public function blocks(Request $request): JsonResponse
    {
        $query = AcademicBlock::query()->where('source', ClearanceService::SOURCE)->with(['student.studyProgram', 'exemptions'])->latest('effective_from')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->whereIn('status', ['active', 'exempted']))
            ->when($request->filled('search'), fn ($q) => $q->whereIn('student_id', Student::query()->select('id')->where(fn ($s) => $s->where('nim', 'like', '%'.$request->string('search').'%')->orWhere('name', 'like', '%'.$request->string('search').'%'))));

        return $this->paginate($query, $request, fn (AcademicBlock $b) => Presenter::block($b));
    }

    public function showBlock(AcademicBlock $academicBlock): JsonResponse
    {
        abort_unless($academicBlock->source === ClearanceService::SOURCE, 404);

        return ApiResponse::success([
            ...Presenter::block($academicBlock->load(['student.studyProgram', 'exemptions'])),
            'details' => $academicBlock->details,
            'histories' => $academicBlock->histories()->with('actor')->get()->map(fn ($h) => [
                'action' => $h->action, 'from' => $h->from_status, 'to' => $h->to_status, 'actor' => $h->actor?->name ?? 'Sistem', 'reason' => $h->reason, 'payload' => $h->payload, 'created_at' => $h->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function revokeBlock(Request $request, AcademicBlock $academicBlock, AuditLogService $audit): JsonResponse
    {
        abort_unless($academicBlock->source === ClearanceService::SOURCE, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);
        $block = $this->blocks->revoke($academicBlock, $data['reason'], $this->user($request));
        $audit->recordAction($block, AuditAction::ForcedOverride, ['action' => 'manual_revoke'], $data['reason']);

        return ApiResponse::success(Presenter::block($block), 'Cekal dibuka manual. Catatan: evaluasi berikutnya dapat menerbitkan cekal lagi bila kewajiban belum dipenuhi — gunakan dispensasi untuk pengecualian sementara.');
    }

    public function dispensations(Request $request): JsonResponse
    {
        $query = AcademicBlockExemption::query()->whereHas('block', fn ($q) => $q->where('source', ClearanceService::SOURCE))->with(['block.student.studyProgram', 'requester', 'granter'])->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return $this->paginate($query, $request, fn (AcademicBlockExemption $e) => [...Presenter::exemption($e), 'requested_by_name' => $e->requester?->name, 'granted_by_name' => $e->granter?->name]);
    }

    public function grant(Request $request, AcademicBlock $academicBlock): JsonResponse
    {
        $data = $request->validate([
            'service' => ['required', Rule::in(array_keys(AcademicBlockService::SERVICES))],
            'valid_until' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'promise_amount' => ['nullable', 'numeric', 'gt:0'],
            'promise_date' => ['nullable', 'date'],
        ]);
        $exemption = $this->clearance->requestDispensation($academicBlock, $data['service'], CarbonImmutable::parse($data['valid_until']), $data['reason'], $this->user($request), false,
            isset($data['promise_amount']) ? (string) $data['promise_amount'] : null, $data['promise_date'] ?? null);

        return ApiResponse::success(Presenter::exemption($exemption), $exemption->status === BlockExemptionStatus::Active ? 'Dispensasi diberikan.' : 'Dispensasi melebihi batas petugas; menunggu persetujuan pejabat.', status: 201);
    }

    public function decide(Request $request, AcademicBlockExemption $academicBlockExemption): JsonResponse
    {
        $data = $request->validate(['approve' => ['required', 'boolean'], 'note' => ['nullable', 'string', 'max:1000']]);

        if (! $data['approve'] && empty($data['note'])) {
            throw new ConflictException('Alasan penolakan wajib diisi.');
        }

        return ApiResponse::success(Presenter::exemption($this->clearance->decideDispensation($academicBlockExemption, (bool) $data['approve'], $this->user($request), $data['note'] ?? null)), $data['approve'] ? 'Dispensasi disetujui.' : 'Dispensasi ditolak.');
    }

    public function revokeDispensation(Request $request, AcademicBlockExemption $academicBlockExemption): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);

        return ApiResponse::success(Presenter::exemption($this->blocks->revokeExemption($academicBlockExemption, $data['reason'], $this->user($request))), 'Dispensasi dicabut.');
    }

    /** Status bebas tanggungan keuangan (FR-CKL-06) — untuk yudisium/TU. */
    public function studentClearance(Student $student): JsonResponse
    {
        return ApiResponse::success([
            'student' => Presenter::student($student->load('studyProgram')),
            ...$this->clearance->clearance($student),
            'blocks' => AcademicBlock::query()->where('student_id', $student->id)->whereIn('status', ['active', 'exempted'])->get()->map(fn ($b) => Presenter::block($b)),
        ]);
    }

    /** @return array<string, mixed> */
    private function validateRule(Request $request, ?FinanceClearanceRule $rule = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('finance_clearance_rules', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($rule?->id)],
            'name' => ['required', 'string', 'max:150'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => [Rule::in(AcademicBlockService::ENFORCED_SERVICES)],
            'requirement' => ['required', Rule::enum(ClearanceRequirement::class)],
            'bill_groups' => ['nullable', 'array'],
            'bill_groups.*' => [Rule::enum(BillGroup::class)],
            'terms' => ['required', Rule::in(['current', 'previous', 'current_and_previous', 'all'])],
            'grace_days' => ['required', 'integer', 'between:0,90'],
            'min_outstanding_amount' => ['required', 'numeric', 'min:0'],
            'include_penalties' => ['boolean'],
            'study_program_id' => ['nullable', 'string', Rule::exists('study_programs', 'id')],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'is_active' => ['boolean'],
        ]);
        $data['code'] = strtoupper($data['code']);

        return $data;
    }
}
