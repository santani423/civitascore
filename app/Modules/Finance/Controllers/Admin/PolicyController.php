<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\Student;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\PenaltyCalcType;
use Modules\Finance\Enums\PenaltyPeriodUnit;
use Modules\Finance\Enums\PenaltyRuleStatus;
use Modules\Finance\Models\InstallmentPlan;
use Modules\Finance\Models\InstallmentRequest;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\PenaltyAccrual;
use Modules\Finance\Models\PenaltyRule;
use Modules\Finance\Models\StudentUktAssignment;
use Modules\Finance\Models\UktChangeRequest;
use Modules\Finance\Models\UktGroup;
use Modules\Finance\Services\InstallmentService;
use Modules\Finance\Services\PenaltyService;
use Modules\Finance\Services\UktService;
use Modules\Finance\Support\Presenter;

/** Denda (F-08), golongan UKT (F-04), dan cicilan (F-07). */
class PolicyController extends KeuController
{
    // ---------- Denda ----------

    public function penaltyRules(): JsonResponse
    {
        return ApiResponse::success(PenaltyRule::query()->with(['submitter', 'approver'])->latest()->get()->map(fn (PenaltyRule $r) => $this->presentRule($r)));
    }

    public function storePenaltyRule(Request $request): JsonResponse
    {
        $rule = PenaltyRule::query()->create([...$this->validateRule($request), 'status' => PenaltyRuleStatus::Draft, 'created_by' => $this->user($request)->id]);

        return ApiResponse::success($this->presentRule($rule), 'Aturan denda dibuat (draf).', status: 201);
    }

    public function updatePenaltyRule(Request $request, PenaltyRule $penaltyRule): JsonResponse
    {
        if ($penaltyRule->status !== PenaltyRuleStatus::Draft) {
            throw new ConflictException('Aturan yang sudah diajukan/aktif tidak dapat diubah; nonaktifkan lalu buat aturan baru.');
        }

        $penaltyRule->update($this->validateRule($request, $penaltyRule));

        return ApiResponse::success($this->presentRule($penaltyRule->fresh()), 'Aturan denda diperbarui.');
    }

    public function submitPenaltyRule(Request $request, PenaltyRule $penaltyRule, PenaltyService $penalties): JsonResponse
    {
        return ApiResponse::success($this->presentRule($penalties->submit($penaltyRule, $this->user($request))), 'Aturan denda diajukan.');
    }

    public function approvePenaltyRule(Request $request, PenaltyRule $penaltyRule, PenaltyService $penalties): JsonResponse
    {
        return ApiResponse::success($this->presentRule($penalties->approve($penaltyRule, $this->user($request))), 'Aturan denda disetujui dan aktif.');
    }

    public function deactivatePenaltyRule(PenaltyRule $penaltyRule): JsonResponse
    {
        $penaltyRule->forceFill(['status' => PenaltyRuleStatus::Inactive])->save();

        return ApiResponse::success($this->presentRule($penaltyRule), 'Aturan denda dinonaktifkan; denda berjalan tidak bertambah lagi.');
    }

    public function accruals(Request $request): JsonResponse
    {
        $query = PenaltyAccrual::query()->with(['invoice.student', 'rule', 'installment', 'penaltyInvoice'])->latest('updated_at')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return $this->paginate($query, $request, fn (PenaltyAccrual $a) => [
            'id' => $a->id, 'invoice' => Presenter::invoice($a->invoice->load('student')), 'rule' => $a->rule?->name, 'installment_seq' => $a->installment?->seq,
            'overdue_from' => $a->overdue_from?->toDateString(), 'days' => $a->periods_counted, 'amount' => (string) $a->accrued_amount,
            'status' => $a->status->value, 'status_label' => $a->status->label(), 'penalty_invoice_id' => $a->penalty_invoice_id,
            'penalty_invoice_number' => $a->penaltyInvoice?->invoice_number, 'last_calculated_on' => $a->last_calculated_on?->toDateString(),
        ]);
    }

    public function runAccrual(PenaltyService $penalties): JsonResponse
    {
        return ApiResponse::success(['updated' => $penalties->accrueAll()], 'Perhitungan denda berjalan selesai.');
    }

    public function crystallize(Request $request, PenaltyAccrual $penaltyAccrual, PenaltyService $penalties): JsonResponse
    {
        $invoice = $penalties->crystallize($penaltyAccrual, $this->user($request));

        return ApiResponse::success($invoice ? Presenter::invoice($invoice) : null, $invoice ? 'Denda ditagihkan.' : 'Denda nol; dihentikan.');
    }

    public function waiveAccrual(Request $request, PenaltyAccrual $penaltyAccrual, PenaltyService $penalties): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);

        return ApiResponse::success(['status' => $penalties->waiveAccrual($penaltyAccrual, $data['reason'], $this->user($request))->status->value], 'Denda berjalan dihapus.');
    }

    // ---------- UKT ----------

    public function uktGroups(): JsonResponse
    {
        return ApiResponse::success(UktGroup::query()->withCount(['assignments' => fn ($q) => $q->where('status', 'active')])->orderBy('level')->get());
    }

    public function storeUktGroup(Request $request): JsonResponse
    {
        return ApiResponse::success(UktGroup::query()->create($this->validateGroup($request)), 'Golongan UKT dibuat.', status: 201);
    }

    public function updateUktGroup(Request $request, UktGroup $uktGroup): JsonResponse
    {
        $uktGroup->update($this->validateGroup($request, $uktGroup));

        return ApiResponse::success($uktGroup->fresh(), 'Golongan UKT diperbarui.');
    }

    public function uktAssignments(Request $request): JsonResponse
    {
        $query = StudentUktAssignment::query()->with(['student.studyProgram', 'group', 'effectiveFromTerm', 'assigner'])->latest()
            ->when(! $request->boolean('include_history'), fn ($q) => $q->where('status', 'active'))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->string('student_id')))
            ->when($request->filled('ukt_group_id'), fn ($q) => $q->where('ukt_group_id', $request->string('ukt_group_id')))
            ->when($request->filled('search'), fn ($q) => $q->whereIn('student_id', Student::query()->select('id')->where(fn ($s) => $s->where('nim', 'like', '%'.$request->string('search').'%')->orWhere('name', 'like', '%'.$request->string('search').'%'))));

        return $this->paginate($query, $request, fn (StudentUktAssignment $a) => [
            'id' => $a->id, 'student' => Presenter::student($a->student), 'group' => $a->group?->only(['id', 'code', 'name']), 'effective_from' => $a->effectiveFromTerm?->label(),
            'basis' => $a->basis, 'decree_number' => $a->decree_number, 'status' => $a->status, 'assigned_by' => $a->assigner?->name, 'created_at' => $a->created_at?->toIso8601String(),
        ]);
    }

    public function assignUkt(Request $request, UktService $ukt): JsonResponse
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:500'],
            'ukt_group_id' => ['required', 'string'],
            'effective_term_id' => ['nullable', 'string', Rule::exists('academic_terms', 'id')],
            'basis' => ['required', 'string', 'min:5', 'max:2000'],
            'decree_number' => ['nullable', 'string', 'max:60'],
        ]);
        $group = UktGroup::query()->findOrFail($data['ukt_group_id']);
        $count = 0;

        foreach (Student::query()->whereIn('id', $data['student_ids'])->get() as $student) {
            $ukt->assign($student, $group, $data['effective_term_id'] ?? null, $data['basis'], $data['decree_number'] ?? null, $this->user($request));
            $count++;
        }

        return ApiResponse::success(['assigned' => $count], "Golongan UKT ditetapkan untuk {$count} mahasiswa.");
    }

    public function uktRequests(Request $request): JsonResponse
    {
        $query = UktChangeRequest::query()->with(['student.studyProgram', 'currentGroup', 'requestedGroup', 'decidedGroup', 'decider'])->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return $this->paginate($query, $request, fn (UktChangeRequest $r) => [
            'id' => $r->id, 'student' => Presenter::student($r->student), 'current_group' => $r->currentGroup?->name, 'requested_group' => $r->requestedGroup?->name,
            'decided_group' => $r->decidedGroup?->name, 'reason' => $r->reason, 'evidence_file_id' => $r->evidence_file_id, 'status' => $r->status->value,
            'status_label' => $r->status->label(), 'decision_note' => $r->decision_note, 'decided_by' => $r->decider?->name, 'created_at' => $r->created_at?->toIso8601String(),
        ]);
    }

    public function decideUktRequest(Request $request, UktChangeRequest $uktChangeRequest, UktService $ukt): JsonResponse
    {
        $data = $request->validate([
            'approve' => ['required', 'boolean'],
            'ukt_group_id' => ['required_if:approve,true', 'nullable', 'string'],
            'effective_term_id' => ['nullable', 'string', Rule::exists('academic_terms', 'id')],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $group = ! empty($data['ukt_group_id']) ? UktGroup::query()->findOrFail($data['ukt_group_id']) : null;
        $ukt->decide($uktChangeRequest, (bool) $data['approve'], $group, $data['effective_term_id'] ?? null, $data['note'], $this->user($request));

        return ApiResponse::success(null, $data['approve'] ? 'Pengajuan disetujui; golongan baru berlaku untuk tagihan berikutnya.' : 'Pengajuan ditolak.');
    }

    // ---------- Cicilan ----------

    public function plans(): JsonResponse
    {
        return ApiResponse::success(InstallmentPlan::query()->orderBy('code')->get());
    }

    public function storePlan(Request $request): JsonResponse
    {
        return ApiResponse::success(InstallmentPlan::query()->create([...$this->validatePlan($request), 'created_by' => $this->user($request)->id]), 'Skema cicilan dibuat.', status: 201);
    }

    public function updatePlan(Request $request, InstallmentPlan $installmentPlan): JsonResponse
    {
        $installmentPlan->update($this->validatePlan($request, $installmentPlan));

        return ApiResponse::success($installmentPlan->fresh(), 'Skema cicilan diperbarui (jadwal yang sudah berjalan tidak berubah).');
    }

    public function installmentRequests(Request $request): JsonResponse
    {
        $query = InstallmentRequest::query()->with(['student.studyProgram', 'invoice', 'plan', 'decider'])->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return $this->paginate($query, $request, fn (InstallmentRequest $r) => [
            'id' => $r->id, 'student' => Presenter::student($r->student), 'invoice' => Presenter::invoice($r->invoice), 'plan' => $r->plan?->name, 'terms' => $r->terms,
            'kind' => $r->kind, 'status' => $r->status->value, 'status_label' => $r->status->label(), 'plan_status' => $r->plan_status, 'reason' => $r->reason,
            'requested_by' => $r->requested_by, 'decided_by' => $r->decider?->name, 'decision_note' => $r->decision_note, 'created_at' => $r->created_at?->toIso8601String(),
        ]);
    }

    public function requestInstallment(Request $request, Invoice $invoice, InstallmentService $installments): JsonResponse
    {
        $data = $this->validateInstallmentRequest($request);
        $plan = isset($data['installment_plan_id']) ? InstallmentPlan::query()->findOrFail($data['installment_plan_id']) : null;
        $result = $installments->request($invoice, $plan, $data['terms'] ?? null, $data['reason'] ?? null, $this->user($request), $data['kind'] ?? 'new');

        return ApiResponse::success(['id' => $result->id, 'status' => $result->status->value], $result->status->value === 'approved' ? 'Jadwal cicilan dibuat.' : 'Pengajuan cicilan dicatat dan menunggu persetujuan.', status: 201);
    }

    public function approveInstallment(Request $request, InstallmentRequest $installmentRequest, InstallmentService $installments): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $installments->approve($installmentRequest, $this->user($request), $data['note'] ?? null);

        return ApiResponse::success(null, 'Cicilan disetujui; jadwal termin dibuat.');
    }

    public function rejectInstallment(Request $request, InstallmentRequest $installmentRequest, InstallmentService $installments): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:1000']]);
        $installments->reject($installmentRequest, $this->user($request), $data['note']);

        return ApiResponse::success(null, 'Pengajuan cicilan ditolak.');
    }

    public function cancelSchedule(Request $request, Invoice $invoice, InstallmentService $installments): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);
        $installments->cancelSchedule($invoice, $data['reason'], $this->user($request));

        return ApiResponse::success(null, 'Jadwal cicilan dibatalkan; tagihan kembali ke jatuh tempo semula.');
    }

    /** @return array<string, mixed> */
    public static function validateInstallmentRequest(Request $request): array
    {
        return $request->validate([
            'installment_plan_id' => ['nullable', 'string'],
            'terms' => ['nullable', 'array', 'min:2', 'max:12'],
            'terms.*.percent' => ['required_with:terms', 'numeric', 'gt:0', 'max:100'],
            'terms.*.due_date' => ['nullable', 'date'],
            'terms.*.due_offset_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'kind' => ['nullable', Rule::in(['new', 'reschedule'])],
        ]);
    }

    /** @return array<string, mixed> */
    private function presentRule(PenaltyRule $r): array
    {
        return [
            ...$r->only(['id', 'code', 'name', 'bill_groups', 'grace_days', 'max_periods', 'decree_reference']),
            'calc_type' => $r->calc_type->value, 'calc_type_label' => $r->calc_type->label(), 'period_unit' => $r->period_unit->value, 'period_unit_label' => $r->period_unit->label(),
            'rate_percent' => $r->rate_percent !== null ? (string) $r->rate_percent : null, 'flat_amount' => $r->flat_amount !== null ? (string) $r->flat_amount : null,
            'cap_amount' => $r->cap_amount !== null ? (string) $r->cap_amount : null, 'cap_percent' => $r->cap_percent !== null ? (string) $r->cap_percent : null,
            'effective_from' => $r->effective_from?->toDateString(), 'effective_until' => $r->effective_until?->toDateString(),
            'status' => $r->status->value, 'status_label' => $r->status->label(), 'created_by' => $r->created_by, 'submitted_by' => $r->submitted_by,
            'approved_by' => $r->relationLoaded('approver') ? $r->approver?->name : null,
        ];
    }

    /** @return array<string, mixed> */
    private function validateRule(Request $request, ?PenaltyRule $rule = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('penalty_rules', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($rule?->id)],
            'name' => ['required', 'string', 'max:150'],
            'bill_groups' => ['required', 'array', 'min:1'],
            'bill_groups.*' => [Rule::enum(BillGroup::class), Rule::notIn([BillGroup::Penalty->value])],
            'grace_days' => ['required', 'integer', 'between:0,90'],
            'calc_type' => ['required', Rule::enum(PenaltyCalcType::class)],
            'period_unit' => ['required', Rule::enum(PenaltyPeriodUnit::class)],
            'rate_percent' => ['required_if:calc_type,percent', 'nullable', 'numeric', 'gt:0', 'max:100'],
            'flat_amount' => ['required_if:calc_type,flat', 'nullable', 'numeric', 'gt:0'],
            'cap_amount' => ['nullable', 'numeric', 'gt:0'],
            'cap_percent' => ['nullable', 'numeric', 'gt:0', 'max:100'],
            'max_periods' => ['nullable', 'integer', 'min:1'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'decree_reference' => ['nullable', 'string', 'max:120'],
        ]);
        $data['code'] = strtoupper($data['code']);

        return $data;
    }

    /** @return array<string, mixed> */
    private function validateGroup(Request $request, ?UktGroup $group = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('ukt_groups', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($group?->id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:100'],
            'level' => ['required', 'integer', 'between:1,20'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validatePlan(Request $request, ?InstallmentPlan $plan = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('installment_plans', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($plan?->id)],
            'name' => ['required', 'string', 'max:150'],
            'bill_groups' => ['required', 'array', 'min:1'],
            'bill_groups.*' => [Rule::enum(BillGroup::class)],
            'terms' => ['required', 'array', 'min:2', 'max:12'],
            'terms.*.percent' => ['required', 'numeric', 'gt:0', 'max:100'],
            'terms.*.due_date' => ['nullable', 'date'],
            'terms.*.due_offset_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'min_first_payment' => ['nullable', 'numeric', 'min:0'],
            'auto_approve' => ['boolean'],
            'require_no_arrears' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if (abs(array_sum(array_map(fn ($t) => (float) $t['percent'], $data['terms'])) - 100) > 0.001) {
            throw new ConflictException('Jumlah persentase termin harus 100%.');
        }

        $data['code'] = strtoupper($data['code']);

        return $data;
    }
}
