<?php

namespace Modules\Admission\Controllers\Admin;

use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Services\Account\AccountService;
use Modules\Admission\Services\Application\ApplicationService;
use Modules\Admission\Services\Verification\VerificationService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\ApplicationStageResolver;
use Modules\Admission\Support\DocumentReasonCodes;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\ScopeResolver;
use Modules\Admission\Support\SensitiveAccessLogger;

/**
 * Daftar & detail pendaftar untuk staf (FR-APP-ADM, ADM-20/21). Field sensitif
 * selalu tersamar; nilai asli hanya lewat `reveal` (izin + alasan + log, 08 §6).
 */
class ApplicationController extends AdminController
{
    /** Field yang dapat dibuka lewat reveal → [model, atribut]. */
    private const REVEALABLE = [
        'nik' => 'profile', 'passport_number' => 'profile', 'mother_name' => 'profile',
        'special_needs_note' => 'profile', 'kip_number' => 'application',
    ];

    public function __construct(private readonly ApplicationService $applications, private readonly ScopeResolver $scope) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period_id' => ['nullable', 'string'], 'wave_id' => ['nullable', 'string'], 'wave_path_id' => ['nullable', 'string'],
            'admission_path_id' => ['nullable', 'string'], 'study_program_id' => ['nullable', 'string'],
            'form_status' => ['nullable', 'string'], 'verification_status' => ['nullable', 'string'], 'selection_status' => ['nullable', 'string'],
            'registration_payment_status' => ['nullable', 'string'], 'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['created_at', '-created_at', 'submitted_at', '-submitted_at', 'registration_number', '-registration_number'])],
        ]);

        $query = Application::query()->with(['account:id,name,email,phone', 'admissionPath:id,code,name', 'wave:id,code,name', 'choices.studyProgram:id,code,name', 'activeAssignment.reviewer:id,name'])
            ->when($request->filled('period_id'), fn ($q) => $q->where('period_id', $request->string('period_id')))
            ->when($request->filled('wave_id'), fn ($q) => $q->where('wave_id', $request->string('wave_id')))
            ->when($request->filled('wave_path_id'), fn ($q) => $q->where('wave_path_id', $request->string('wave_path_id')))
            ->when($request->filled('admission_path_id'), fn ($q) => $q->where('admission_path_id', $request->string('admission_path_id')))
            ->when($request->filled('study_program_id'), fn ($q) => $q->whereHas('choices', fn ($c) => $c->where('study_program_id', $request->string('study_program_id'))))
            ->when($request->filled('form_status'), fn ($q) => $q->whereIn('form_status', explode(',', $request->string('form_status'))))
            ->when($request->filled('verification_status'), fn ($q) => $q->whereIn('verification_status', explode(',', $request->string('verification_status'))))
            ->when($request->filled('selection_status'), fn ($q) => $q->whereIn('selection_status', explode(',', $request->string('selection_status'))))
            ->when($request->filled('registration_payment_status'), fn ($q) => $q->whereIn('registration_payment_status', explode(',', $request->string('registration_payment_status'))))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('registration_number', 'like', $term)
                    ->orWhereHas('account', fn ($a) => $a->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term)));
            });
        $this->scope->constrainApplications($query, $this->user($request), PmbPermission::APPLICATIONS_READ);

        $sort = $request->string('sort', '-created_at')->toString();
        $query->orderBy(ltrim($sort, '-'), str_starts_with($sort, '-') ? 'desc' : 'asc');

        return $this->paginate($query, $request, fn (Application $a) => [
            'id' => $a->getKey(),
            'registration_number' => $a->registration_number,
            'applicant' => ['id' => $a->account?->getKey(), 'name' => $a->account?->name, 'email' => $a->account?->email, 'phone' => SensitiveAccessLogger::mask($a->account?->phone, 3, 4)],
            'path' => $a->admissionPath?->name,
            'wave' => $a->wave?->name,
            'first_choice' => $a->choices->sortBy('priority')->first()?->studyProgram?->name,
            'form_status' => $a->form_status->value,
            'verification_status' => $a->verification_status->value,
            'selection_status' => $a->selection_status->value,
            'registration_payment_status' => $a->registration_payment_status->value,
            'current_stage' => ApplicationStageResolver::resolve($a),
            'reviewer' => $a->activeAssignment?->reviewer?->name,
            'submitted_at' => $a->submitted_at?->toIso8601String(),
            'created_at' => $a->created_at?->toIso8601String(),
        ]);
    }

    public function show(Request $request, Application $application, VerificationService $verification): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::APPLICATIONS_READ, $application);
        $application->load(['account.profile', 'account.educations.reportScores', 'account.guardians', 'account.achievements', 'period', 'wave', 'wavePath', 'admissionPath', 'formTemplate.fields',
            'choices.studyProgram', 'choices.studySystem', 'answers', 'invoices', 'activeAssignment.reviewer', 'scores.component', 'currentDecision.studyProgram', 'reRegistration', 'conversion', 'duplicateFlags', 'examParticipations.session']);
        $profile = $application->account->profile;
        $fields = $application->formTemplate?->fields->keyBy('id');

        return ApiResponse::success([
            'id' => $application->getKey(),
            'registration_number' => $application->registration_number,
            'lock_version' => $application->lock_version,
            'statuses' => [
                'form' => $application->form_status->value, 'verification' => $application->verification_status->value,
                'selection' => $application->selection_status->value, 'registration_payment' => $application->registration_payment_status->value,
                'current_stage' => ApplicationStageResolver::resolve($application),
            ],
            'account' => [
                'id' => $application->account->getKey(), 'name' => $application->account->name, 'email' => $application->account->email,
                'phone' => $application->account->phone, 'status' => $application->account->status->value,
                'email_verified_at' => $application->account->email_verified_at?->toIso8601String(),
                'assisted' => $application->account->created_by_user_id !== null,
            ],
            'profile' => $profile ? [
                ...$profile->only(['full_name', 'nationality', 'passport_country', 'birth_place', 'gender', 'religion', 'address_line', 'rt', 'rw', 'village', 'district', 'city', 'province', 'region_code', 'postal_code']),
                'birth_date' => $profile->birth_date?->toDateString(),
                'nik_masked' => SensitiveAccessLogger::mask($profile->nik, 4),
                'passport_number_masked' => SensitiveAccessLogger::mask($profile->passport_number, 3),
                'mother_name_masked' => $profile->mother_name ? '••••••' : null,
                'has_special_needs' => ! empty($profile->special_needs),
                'special_needs' => $profile->special_needs,
            ] : null,
            'educations' => $application->account->educations->map(fn ($e) => [...$e->only(['id', 'level', 'institution_name', 'institution_npsn', 'school_type', 'major', 'graduation_year', 'is_graduating', 'nisn', 'gpa', 'credits_earned', 'city', 'province', 'country']), 'is_primary' => $e->getKey() === $application->primary_education_id, 'report_scores' => $e->reportScores->map->only(['semester', 'subject_code', 'subject_name', 'score'])]),
            'guardians' => $application->account->guardians->map->only(['id', 'relationship', 'full_name', 'phone', 'email', 'education_level', 'occupation', 'income_range', 'is_deceased']),
            'achievements' => $application->account->achievements->map->only(['id', 'name', 'category', 'level', 'rank', 'year', 'organizer']),
            'period' => $application->period?->only(['id', 'code', 'name']),
            'wave' => $application->wave?->only(['id', 'code', 'name']),
            'wave_path_id' => $application->wave_path_id,
            'path' => $application->admissionPath?->only(['id', 'code', 'name', 'category']),
            'choices' => $application->choices->sortBy('priority')->values()->map(fn ($c) => ['priority' => $c->priority, 'study_program_id' => $c->study_program_id, 'study_program' => $c->studyProgram?->name, 'study_system_id' => $c->study_system_id, 'study_system' => $c->studySystem?->name]),
            'answers' => $application->answers->map(function ($a) use ($fields) {
                $field = $fields?->get($a->form_field_id);

                return ['field_id' => $a->form_field_id, 'label' => $field?->label, 'section' => $field?->section, 'value' => $field?->is_sensitive ? '••••••' : ($a->value_json ?? $a->value_text)];
            })->values(),
            'application_data' => [
                ...$application->only(['info_source', 'info_source_detail', 'referral_code', 'kip_registration_number', 'kip_status', 'scholarship_interest', 'scholarship_program_code', 'rejection_reason_code', 'withdraw_reason']),
                'kip_number_masked' => SensitiveAccessLogger::mask($application->kip_number, 4),
                'revision_targets' => $application->revision_targets,
                'revision_due_at' => $application->revision_due_at?->toIso8601String(),
                'submitted_at' => $application->submitted_at?->toIso8601String(),
                'verified_at' => $application->verified_at?->toIso8601String(),
                'withdrawn_at' => $application->withdrawn_at?->toIso8601String(),
                'declarations' => $application->declarations,
            ],
            'invoices' => $application->invoices->map->only(['id', 'type', 'invoice_number', 'amount_net', 'amount_paid', 'status', 'due_at', 'needs_resolution']),
            'assignment' => $application->activeAssignment ? ['reviewer_id' => $application->activeAssignment->reviewer_user_id, 'reviewer' => $application->activeAssignment->reviewer?->name, 'claimed_until' => $application->activeAssignment->claimed_until?->toIso8601String(), 'is_mine' => $application->activeAssignment->reviewer_user_id === $this->user($request)->getKey()] : null,
            'unmet_for_verification' => $verification->unmetForVerification($application),
            'scores' => $application->scores->map(fn ($s) => ['id' => $s->getKey(), 'component' => $s->component?->name, 'component_code' => $s->component?->code, 'raw_score' => $s->raw_score, 'passed' => $s->passed, 'source' => $s->source?->value ?? $s->source, 'locked' => $s->locked_at !== null]),
            'exams' => $application->examParticipations->map(fn ($p) => ['participant_number' => $p->participant_number, 'session' => $p->session?->code, 'starts_at' => $p->session?->starts_at?->toIso8601String(), 'attendance_status' => $p->attendance_status->value]),
            'decision' => $application->currentDecision ? ['id' => $application->currentDecision->getKey(), 'decision' => $application->currentDecision->decision->value, 'program' => $application->currentDecision->studyProgram?->name, 'publication_status' => $application->currentDecision->publication_status->value, 'version' => $application->currentDecision->decision_version, 'scholarship_award' => $application->currentDecision->scholarship_award] : null,
            're_registration' => $application->reRegistration ? ['id' => $application->reRegistration->getKey(), 'status' => $application->reRegistration->status->value] : null,
            'conversion' => $application->conversion ? ['id' => $application->conversion->getKey(), 'status' => $application->conversion->status->value, 'nim' => $application->conversion->nim] : null,
            'duplicate_flags' => $application->duplicateFlags->where('status.value', 'open')->values()->map->only(['id', 'type', 'similarity_score', 'status']),
            'can' => [
                'reveal' => $this->user($request)->hasPermissionTo(PmbPermission::SENSITIVE_READ),
                'correct' => $this->user($request)->hasPermissionTo(PmbPermission::APPLICATIONS_CORRECT),
                'verify' => $this->user($request)->hasPermissionTo(PmbPermission::VERIFICATION_DECIDE),
                'override' => $this->user($request)->hasPermissionTo(PmbPermission::VERIFICATION_OVERRIDE),
            ],
        ]);
    }

    public function timeline(Request $request, Application $application): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::APPLICATIONS_READ, $application);
        $users = User::query()->whereIn('id', $application->histories()->where('actor_type', 'user')->pluck('actor_id'))->pluck('name', 'id');

        return ApiResponse::success($application->histories()->orderByDesc('created_at')->orderByDesc('id')->get()->map(fn ($h) => [
            'id' => $h->getKey(), 'lifecycle' => $h->lifecycle?->value ?? $h->lifecycle, 'from' => $h->from_status, 'to' => $h->to_status,
            'actor_type' => $h->actor_type, 'actor' => $h->actor_type === 'user' ? ($users[$h->actor_id] ?? null) : ($h->actor_type === 'applicant' ? 'Pendaftar' : 'Sistem'),
            'reason_code' => $h->reason_code, 'reason_note' => $h->reason_note, 'created_at' => $h->created_at?->toIso8601String(),
        ]));
    }

    public function documents(Request $request, Application $application): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::APPLICATIONS_READ, $application);
        $reviewers = [];

        $documents = $application->documents()->with(['documentType', 'versions.reviews', 'versions.fileUpload'])->orderBy('stage')->get()->map(function (ApplicationDocument $d) use (&$reviewers) {
            return [
                'id' => $d->getKey(), 'type' => $d->documentType?->name, 'type_code' => $d->documentType?->code, 'stage' => $d->stage->value, 'slot' => $d->slot,
                'is_mandatory' => $d->is_mandatory, 'status' => $d->status->value, 'locked' => $d->locked_at !== null,
                'requires_original_check' => (bool) $d->documentType?->requires_original_check, 'is_sensitive' => (bool) $d->documentType?->is_sensitive,
                'current_version_id' => $d->current_version_id,
                'versions' => $d->versions->sortByDesc('version_no')->values()->map(fn ($v) => [
                    'id' => $v->getKey(), 'version_no' => $v->version_no, 'mime_type' => $v->fileUpload?->mime_type, 'size' => $v->fileUpload?->size_bytes,
                    'scan_status' => $v->fileUpload?->status?->value, 'uploaded_by_type' => $v->uploaded_by_type, 'created_at' => $v->created_at?->toIso8601String(),
                    'reviews' => $v->reviews->map(fn ($r) => ['decision' => $r->decision->value, 'reason_code' => $r->reason_code, 'note_for_applicant' => $r->note_for_applicant, 'internal_note' => $r->internal_note, 'reviewed_by' => $r->reviewed_by, 'created_at' => $r->created_at?->toIso8601String()]),
                ]),
            ];
        });

        return ApiResponse::success([
            'documents' => $documents,
            'notes' => $application->notes()->orderByDesc('created_at')->get()->map(fn ($n) => [...$n->only(['id', 'context', 'visibility', 'body', 'target_fields']), 'author' => $n->author_id ? User::query()->whereKey($n->author_id)->value('name') : 'Sistem', 'due_at' => $n->due_at?->toIso8601String(), 'created_at' => $n->created_at?->toIso8601String()]),
            'reason_codes' => ['document' => DocumentReasonCodes::DOCUMENT, 'application_rejection' => DocumentReasonCodes::APPLICATION_REJECTION],
        ]);
    }

    public function storeNote(Request $request, Application $application, VerificationService $verification): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::APPLICATIONS_READ, $application);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000'], 'visibility' => ['required', Rule::in(['internal', 'applicant'])]]);

        return ApiResponse::success($verification->addNote($application, $data['visibility'], $data['body'], $this->user($request), 'general')->only(['id', 'body', 'visibility', 'created_at']), 'Catatan disimpan.', status: 201);
    }

    /** Buka nilai sensitif: alasan wajib, dicatat di log akses data sensitif (08 §6). */
    public function reveal(Request $request, Application $application, SensitiveAccessLogger $logger): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::APPLICATIONS_READ, $application);
        $data = $request->validate(['field' => ['required', Rule::in(array_keys(self::REVEALABLE))], 'purpose' => ['required', 'string', 'min:5', 'max:255']]);
        $subject = self::REVEALABLE[$data['field']] === 'profile' ? $application->account->profile : $application;
        abort_if($subject === null, 404);
        $logger->log($this->user($request), $subject, $data['field'], $data['purpose'], $request->ip());

        return ApiResponse::success(['field' => $data['field'], 'value' => $subject->{$data['field']}])->header('Cache-Control', 'no-store');
    }

    public function correct(Request $request, Application $application): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::APPLICATIONS_CORRECT, $application);
        $data = $request->validate([
            'changes' => ['required', 'array', 'min:1'],
            'changes.full_name' => ['sometimes', 'string', 'max:150'],
            'changes.nik' => ['sometimes', 'nullable', 'digits:16'],
            'changes.passport_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'changes.birth_date' => ['sometimes', 'date', 'before:today'],
            'changes.birth_place' => ['sometimes', 'string', 'max:100'],
            'changes.gender' => ['sometimes', 'nullable', Rule::in(['male', 'female'])],
            'changes.mother_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'changes.*' => ['nullable'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->applications->correctByStaff($application, $data['changes'], $data['reason'], $this->user($request));

        return ApiResponse::success(['id' => $application->getKey()], 'Data dikoreksi.');
    }

    public function changeChoices(Request $request, Application $application): JsonResponse
    {
        $this->assertApplicationScope($request, PmbPermission::APPLICATIONS_CORRECT, $application);
        $data = $request->validate([
            'choices' => ['required', 'array', 'min:1', 'max:5'],
            'choices.*.study_program_id' => ['required', 'string'],
            'choices.*.study_system_id' => ['required', 'string'],
            'lock_version' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $updated = $this->applications->saveChoices($application, $data['choices'], (int) $data['lock_version'], Actor::user($this->user($request)), $data['reason']);

        return ApiResponse::success(['lock_version' => $updated->lock_version], 'Pilihan prodi diubah.');
    }

    public function suspend(Request $request, ApplicantAccount $applicantAccount, AccountService $accounts): JsonResponse
    {
        $data = $request->validate(['suspended' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:5', 'max:255']]);
        $account = $accounts->setSuspended($applicantAccount, (bool) $data['suspended'], $data['reason'], $this->user($request));

        return ApiResponse::success(['status' => $account->status->value], $data['suspended'] ? 'Akun dibekukan.' : 'Pembekuan dicabut.');
    }

    /** Pendaftaran dibantu petugas (FR-ACC-08, ADM-22): akun + draft sekaligus. */
    public function assisted(Request $request, AccountService $accounts): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'wave_path_id' => ['nullable', 'string', 'size:26'],
            'info_source' => ['nullable', 'string', 'max:50'],
        ]);

        $account = $accounts->register($data, $request->ip(), $request->userAgent(), $this->user($request));
        $application = ! empty($data['wave_path_id']) ? $this->applications->createDraft($account, $data['wave_path_id'], Actor::user($this->user($request))) : null;

        return ApiResponse::success([
            'account_id' => $account->getKey(),
            'application_id' => $application?->getKey(),
            'registration_number' => $application?->registration_number,
        ], 'Akun pendaftar dibuat. Tautan aktivasi dikirim ke email pendaftar.', status: 201);
    }
}
