<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Controllers\Applicant\PaymentController;
use Modules\Admission\Enums\CheckStatus;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\ReRegistration;
use Modules\Admission\Models\StudentConversion;
use Modules\Admission\Services\Announcement\DecisionService;
use Modules\Admission\Services\Conversion\ConversionService;
use Modules\Admission\Services\ReRegistration\ReRegistrationService;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\ScopeResolver;

/** Validasi daftar ulang, perpanjangan, pembatalan, konversi mahasiswa (FR-REG, FR-CNV, ADM-65..70). */
class ReRegistrationController extends AdminController
{
    public function __construct(private readonly ReRegistrationService $reRegistrations) {}

    public function index(Request $request, ScopeResolver $scope): JsonResponse
    {
        $query = ReRegistration::query()->with(['application:id,registration_number,applicant_account_id,period_id,wave_id', 'application.account:id,name,email', 'decision.studyProgram:id,name', 'decision.studySystem:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->when($request->filled('period_id'), fn ($q) => $q->whereHas('application', fn ($a) => $a->where('period_id', $request->string('period_id'))))
            ->when($request->filled('wave_id'), fn ($q) => $q->whereHas('application', fn ($a) => $a->where('wave_id', $request->string('wave_id'))))
            ->when($request->filled('study_program_id'), fn ($q) => $q->whereHas('decision', fn ($d) => $d->where('study_program_id', $request->string('study_program_id'))))
            ->when($request->filled('search'), fn ($q) => $q->whereHas('application', fn ($a) => $a->where('registration_number', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('account', fn ($ac) => $ac->where('name', 'like', '%'.$request->string('search').'%'))))
            ->orderByRaw("case when status = 'submitted' then 0 else 1 end")->orderBy('submitted_at')->orderByDesc('created_at');
        $scope->constrainApplications($query, $this->user($request), PmbPermission::REREG_READ, 'application_id');

        return $this->paginate($query, $request, fn (ReRegistration $r) => [
            'id' => $r->getKey(), 'status' => $r->status->value, 'application_id' => $r->application_id,
            'registration_number' => $r->application?->registration_number, 'name' => $r->application?->account?->name,
            'program' => $r->decision?->studyProgram?->name, 'study_system' => $r->decision?->studySystem?->name,
            'deadline_at' => $r->effectiveDeadline()->toIso8601String(), 'extended' => $r->extended_until !== null,
            'submitted_at' => $r->submitted_at?->toIso8601String(), 'approved_at' => $r->approved_at?->toIso8601String(),
        ]);
    }

    public function show(Request $request, ReRegistration $reRegistration): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::REREG_READ, $reRegistration->application_id);
        $reRegistration->load(['application.account.profile', 'application.account.guardians', 'application.conversion', 'decision.studyProgram', 'decision.studySystem', 'checks', 'approver:id,name']);
        $profile = $reRegistration->application->account->profile;
        $invoice = PmbInvoice::query()->where('application_id', $reRegistration->application_id)->where('type', 'initial_tuition')->whereNot('status', 'cancelled')->with(['items', 'transactions.manualProof'])->latest()->first();
        $documents = $reRegistration->application->documents()->where('stage', 're_registration')->with(['documentType:id,code,name,requires_original_check', 'currentVersion'])->get();

        return ApiResponse::success([
            'id' => $reRegistration->getKey(), 'status' => $reRegistration->status->value, 'lock_version' => $reRegistration->lock_version,
            'application_id' => $reRegistration->application_id, 'registration_number' => $reRegistration->application->registration_number,
            'name' => $reRegistration->application->account->name, 'email' => $reRegistration->application->account->email,
            'program' => $reRegistration->decision?->studyProgram?->name, 'study_system' => $reRegistration->decision?->studySystem?->name,
            'deadline_at' => $reRegistration->effectiveDeadline()->toIso8601String(), 'extended_until' => $reRegistration->extended_until?->toIso8601String(), 'extension_reason' => $reRegistration->extension_reason,
            'revision_targets' => $reRegistration->revision_targets, 'almamater_size' => $reRegistration->almamater_size,
            'declarations' => $reRegistration->declarations,
            'pddikti' => $profile ? [
                ...$profile->only(ReRegistrationService::PDDIKTI_PROFILE_FIELDS),
                'mother_name' => $profile->mother_name ? '••••••' : null,
                ...array_intersect_key((array) ($profile->pddikti_extra ?? []), array_flip(ReRegistrationService::PDDIKTI_EXTRA_FIELDS)),
            ] : null,
            'guardians' => $reRegistration->application->account->guardians->map->only(['relationship', 'full_name', 'phone', 'occupation', 'is_deceased']),
            'checks' => $this->reRegistrations->evaluateChecks($reRegistration),
            'documents' => $documents->map(fn ($d) => ['id' => $d->getKey(), 'type' => $d->documentType?->name, 'code' => $d->documentType?->code, 'status' => $d->status->value, 'is_mandatory' => $d->is_mandatory, 'current_version_id' => $d->current_version_id, 'requires_original_check' => (bool) $d->documentType?->requires_original_check]),
            'invoice' => $invoice ? PaymentController::presentInvoice($invoice) : null,
            'conversion' => $reRegistration->application->conversion ? ['id' => $reRegistration->application->conversion->getKey(), 'status' => $reRegistration->application->conversion->status->value, 'nim' => $reRegistration->application->conversion->nim] : null,
            'approved_by' => $reRegistration->approver?->name,
            'timestamps' => collect(['confirmed_at', 'submitted_at', 'approved_at', 'enrolled_at', 'declined_at', 'expired_at', 'cancelled_at'])->mapWithKeys(fn ($k) => [$k => $reRegistration->{$k}?->toIso8601String()]),
        ]);
    }

    public function markCheck(Request $request, ReRegistration $reRegistration, string $code): JsonResponse
    {
        abort_unless(array_key_exists($code, ReRegistrationService::CHECKS), 404);
        $this->assertApplicationScope($request, PmbPermission::REREG_DECIDE, $reRegistration->application_id);
        $data = $request->validate(['status' => ['required', Rule::in(CheckStatus::values())], 'note' => ['nullable', 'string', 'max:255']]);
        $check = $this->reRegistrations->markCheck($reRegistration, $code, $data['status'], $data['note'] ?? null, $this->user($request));

        return ApiResponse::success($check->only(['check_code', 'status', 'note', 'checked_at']), 'Pemeriksaan dicatat.');
    }

    public function requestRevision(Request $request, ReRegistration $reRegistration): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::REREG_DECIDE, $reRegistration->application_id);
        $data = $request->validate([
            'targets' => ['required', 'array'],
            'targets.fields' => ['nullable', 'array'],
            'targets.fields.*' => ['string', 'max:60'],
            'targets.documents' => ['nullable', 'array'],
            'targets.documents.*' => ['string', 'max:30'],
            'note' => ['required', 'string', 'min:5', 'max:2000'],
        ]);
        $updated = $this->reRegistrations->requestRevision($reRegistration, $data['targets'], $data['note'], $this->user($request));

        return ApiResponse::success(['status' => $updated->status->value], 'Permintaan perbaikan dikirim.');
    }

    public function approve(Request $request, ReRegistration $reRegistration): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::REREG_DECIDE, $reRegistration->application_id);
        $data = $request->validate(['lock_version' => ['required', 'integer']]);
        $conversion = $this->reRegistrations->approve($reRegistration, (int) $data['lock_version'], $this->user($request));

        return ApiResponse::success(['conversion_id' => $conversion->getKey(), 'conversion_status' => $conversion->status->value], 'Daftar ulang disetujui; konversi mahasiswa diproses.');
    }

    public function extend(Request $request, ReRegistration $reRegistration): JsonResponse
    {
        $data = $request->validate(['until' => ['required', 'date', 'after:now'], 'reason' => ['required', 'string', 'min:5', 'max:255']]);
        $updated = $this->reRegistrations->extend($reRegistration, $data['until'], $data['reason'], $this->user($request));

        return ApiResponse::success(['deadline_at' => $updated->effectiveDeadline()->toIso8601String()], 'Batas daftar ulang diperpanjang.');
    }

    public function restore(Request $request, ReRegistration $reRegistration): JsonResponse
    {
        $data = $request->validate(['reason_code' => ['required', Rule::in(['DECLINE_MISTAKE', 'LATE_CALLBACK'])], 'note' => ['required', 'string', 'min:5', 'max:500']]);
        $updated = $this->reRegistrations->restore($reRegistration, $data['reason_code'], $data['note'], $this->user($request));

        return ApiResponse::success(['status' => $updated->status->value], 'Daftar ulang dipulihkan.');
    }

    public function bulkExtension(Request $request): JsonResponse
    {
        $data = $request->validate([
            'filter' => ['required', 'array'],
            'filter.period_id' => ['nullable', 'string', 'size:26'],
            'filter.wave_id' => ['nullable', 'string', 'size:26'],
            'filter.study_program_id' => ['nullable', 'string'],
            'until' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $change = $this->reRegistrations->requestBulkExtension(array_filter($data['filter']), $data['until'], $data['reason'], $this->user($request));

        return ApiResponse::success(['change_request_id' => $change->getKey(), 'count' => count((array) $change->payload['re_registration_ids'])], 'Perpanjangan massal diajukan untuk persetujuan.', status: 201);
    }

    public function cancel(Request $request, ReRegistration $reRegistration, DecisionService $decisions, PmbFileStorage $storage): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000'], 'evidence' => ['nullable', 'file', 'max:5120']]);
        $evidenceId = $request->hasFile('evidence') ? $storage->store($request->file('evidence'), ['image/jpeg', 'image/png', 'application/pdf'], 5120, ['evidence'], uploadedByUserId: (string) $this->user($request)->getKey())->getKey() : null;
        $change = $decisions->requestCancellation($reRegistration, $data['reason'], $evidenceId, $this->user($request));

        return ApiResponse::success(['change_request_id' => $change->getKey()], 'Pembatalan penerimaan diajukan untuk persetujuan.', status: 201);
    }

    // ------------------------------------------------------------------ Konversi

    public function conversions(Request $request): JsonResponse
    {
        $query = StudentConversion::query()->with(['application:id,registration_number,applicant_account_id', 'application.account:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', explode(',', $request->string('status'))))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (StudentConversion $c) => $this->presentConversion($c));
    }

    public function conversion(StudentConversion $studentConversion): JsonResponse
    {
        $studentConversion->load(['application.account:id,name,email', 'student:id,nim', 'user:id,name,email']);

        return ApiResponse::success([
            ...$this->presentConversion($studentConversion),
            'student' => $studentConversion->student?->only(['id', 'nim']),
            'user' => $studentConversion->user?->only(['id', 'name', 'email']),
            'email_override' => $studentConversion->email_override,
            'steps' => collect(ConversionService::STEPS)->map(fn ($s) => ['step' => $s, 'done' => in_array($s, (array) $studentConversion->steps_completed, true)]),
        ]);
    }

    public function retry(Request $request, StudentConversion $studentConversion, ConversionService $conversions): JsonResponse
    {
        $data = $request->validate(['email_override' => ['nullable', 'email:rfc', 'max:150']]);
        $conversion = $conversions->retry($studentConversion, $this->user($request), $data['email_override'] ?? null);

        return ApiResponse::success($this->presentConversion($conversion), 'Konversi dijadwalkan ulang.');
    }

    /** @return array<string, mixed> */
    private function presentConversion(StudentConversion $c): array
    {
        return [
            'id' => $c->getKey(), 'status' => $c->status->value, 'nim' => $c->nim, 'application_id' => $c->application_id,
            'registration_number' => $c->application?->registration_number, 'name' => $c->application?->account?->name,
            'attempts' => $c->attempts, 'steps_completed' => $c->steps_completed,
            'last_error_code' => $c->last_error_code, 'last_error_message' => $c->last_error_message,
            'next_attempt_at' => $c->next_attempt_at?->toIso8601String(), 'succeeded_at' => $c->succeeded_at?->toIso8601String(), 'created_at' => $c->created_at?->toIso8601String(),
        ];
    }
}
