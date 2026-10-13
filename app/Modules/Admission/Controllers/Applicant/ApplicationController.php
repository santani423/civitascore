<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationAnswer;
use Modules\Admission\Services\Application\ApplicationService;
use Modules\Admission\Services\Configuration\FormTemplateService;
use Modules\Admission\Support\ApplicationStageResolver;
use Modules\Admission\Support\DocumentReasonCodes;
use Modules\Admission\Support\PmbSettings;

/** Formulir pendaftaran pendaftar (docs/pmb/11 §A.5, 04 §D). */
class ApplicationController extends ApplicantController
{
    public function __construct(private readonly ApplicationService $applications, private readonly PmbSettings $settings) {}

    public function openWavePaths(Request $request): JsonResponse
    {
        return ApiResponse::success($this->applications->openWavePaths($this->account($request)));
    }

    public function index(Request $request): JsonResponse
    {
        $apps = $this->account($request)->applications()->with(['admissionPath', 'wave', 'period', 'choices.studyProgram', 'currentDecision', 'reRegistration', 'invoices'])->latest()->get();

        return ApiResponse::success($apps->map(fn (Application $a) => $this->summary($a))->values());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['wave_path_id' => ['required', 'string', 'size:26']]);
        $application = $this->applications->createDraft($this->account($request), $data['wave_path_id'], $this->actor($request));

        return ApiResponse::success($this->summary($application->load(['admissionPath', 'wave', 'period'])), 'Pendaftaran dibuat.', status: 201);
    }

    public function show(Request $request, string $id, FormTemplateService $templates): JsonResponse
    {
        $application = $this->application($request, $id)->load(['admissionPath', 'wave', 'period', 'wavePath', 'choices.studyProgram', 'choices.studySystem', 'answers.field', 'formTemplate', 'primaryEducation', 'currentDecision', 'reRegistration', 'invoices']);

        return ApiResponse::success([
            ...$this->summary($application),
            'config' => $application->config_snapshot,
            'schema' => $templates->schema($application->formTemplate),
            'editable_fields' => $this->applications->editableFields($application),
            'revision_targets' => $application->revision_targets,
            'revision_due_at' => $application->revision_due_at?->toIso8601String(),
            'primary_education_id' => $application->primary_education_id,
            'supporting' => $application->only(['info_source', 'info_source_detail', 'referral_code', 'kip_registration_number', 'kip_status', 'scholarship_interest', 'scholarship_program_code']) + ['kip_number_filled' => $application->kip_number !== null],
            'choices' => $application->choices->map(fn ($c) => ['priority' => $c->priority, 'study_program_id' => $c->study_program_id, 'study_program' => $c->studyProgram?->name, 'study_system_id' => $c->study_system_id, 'study_system' => $c->studySystem?->name])->values(),
            'answers' => $application->answers->mapWithKeys(fn (ApplicationAnswer $a) => [$a->field?->key => $a->field?->is_sensitive ? '••••' : ($a->value_json ?? $a->value_text)]),
            'declarations' => $this->applications->activeDeclarations()->map->only(['id', 'title', 'body', 'version', 'slug'])->values(),
            'offered_choices' => $this->offeredChoices($application),
            'completeness' => $this->applications->completeness($application),
            'lock_version' => $application->lock_version,
        ]);
    }

    public function saveSection(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['section' => ['required', 'in:identity,education,supporting'], 'data' => ['present', 'array'], 'lock_version' => ['required', 'integer']]);
        $application = $this->applications->saveSection($this->application($request, $id), $data['section'], $data['data'], (int) $data['lock_version'], $this->actor($request));

        return ApiResponse::success(['lock_version' => $application->lock_version, 'saved_at' => now()->toIso8601String()], 'Tersimpan.');
    }

    public function saveChoices(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'choices' => ['required', 'array', 'min:1', 'max:5'],
            'choices.*.priority' => ['required', 'integer', 'between:1,5'],
            'choices.*.study_program_id' => ['required', 'string', 'size:26'],
            'choices.*.study_system_id' => ['required', 'string', 'size:26'],
            'lock_version' => ['required', 'integer'],
        ]);
        $application = $this->applications->saveChoices($this->application($request, $id), $data['choices'], (int) $data['lock_version']);

        return ApiResponse::success(['lock_version' => $application->lock_version], 'Pilihan program studi disimpan.');
    }

    public function saveAnswers(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['answers' => ['present', 'array'], 'lock_version' => ['required', 'integer']]);
        $application = $this->applications->saveAnswers($this->application($request, $id), $data['answers'], (int) $data['lock_version']);

        return ApiResponse::success(['lock_version' => $application->lock_version], 'Tersimpan.');
    }

    public function completeness(Request $request, string $id): JsonResponse
    {
        return ApiResponse::success($this->applications->completeness($this->application($request, $id)));
    }

    public function submit(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['declarations' => ['present', 'array'], 'declarations.*' => ['string', 'size:26'], 'lock_version' => ['required', 'integer']]);
        $application = $this->applications->submit($this->application($request, $id), $data['declarations'], (int) $data['lock_version'], $request->ip(), $request->userAgent(), $this->actor($request));

        return ApiResponse::success([
            'id' => $application->getKey(),
            'registration_number' => $application->registration_number,
            'form_status' => $application->form_status->value,
            'verification_status' => $application->verification_status->value,
            'current_stage' => ApplicationStageResolver::resolve($application),
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'lock_version' => $application->lock_version,
        ], 'Formulir berhasil dikirim.');
    }

    public function withdraw(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $application = $this->applications->withdraw($this->application($request, $id), $data['reason'] ?? null, $this->actor($request));

        return ApiResponse::success($this->summary($application), 'Pendaftaran ditarik.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->applications->discardDraft($this->application($request, $id), $this->actor($request));

        return response()->json(null, 204);
    }

    /** Catatan petugas yang terlihat pendaftar (alasan perbaikan/penolakan, 12 §1 transparansi). */
    public function notes(Request $request, string $id): JsonResponse
    {
        $application = $this->application($request, $id);
        $documentReasons = \Modules\Admission\Models\DocumentReview::query()
            ->whereIn('document_version_id', \Modules\Admission\Models\DocumentVersion::query()->whereIn('application_document_id', $application->documents()->select('id'))->select('id'))
            ->with('version.document.documentType')->latest('created_at')->get()
            ->map(fn ($r) => [
                'document' => $r->version?->document?->documentType?->name,
                'version_no' => $r->version?->version_no,
                'decision' => $r->decision->value,
                'reason_code' => in_array($r->reason_code, DocumentReasonCodes::MASKED_FOR_APPLICANT, true) ? null : $r->reason_code,
                'note' => in_array($r->reason_code, DocumentReasonCodes::MASKED_FOR_APPLICANT, true) ? DocumentReasonCodes::MASKED_MESSAGE : $r->note_for_applicant,
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success([
            'notes' => $application->notes()->where('visibility', 'applicant')->get()->map->only(['context', 'body', 'target_fields', 'due_at', 'created_at']),
            'document_reviews' => $documentReasons,
            'rejection_reason_code' => $application->rejection_reason_code,
        ]);
    }

    /** @return array<string, mixed> */
    private function summary(Application $a): array
    {
        $stage = ApplicationStageResolver::resolve($a);
        $registrationInvoice = $a->relationLoaded('invoices') ? $a->invoices->firstWhere('type.value', 'registration_fee') : null;

        return [
            'id' => $a->getKey(),
            'registration_number' => $a->registration_number,
            'period' => $a->period?->only(['id', 'code', 'name']),
            'wave' => $a->wave ? ['id' => $a->wave->getKey(), 'code' => $a->wave->code, 'name' => $a->wave->name, 'registration_closes_at' => $a->wave->registration_closes_at?->toIso8601String(), 'announcement_at' => $a->wave->announcement_at?->toIso8601String(), 'form_lock_at' => $a->wave->form_lock_at?->toIso8601String()] : null,
            'path' => $a->admissionPath ? ['id' => $a->admissionPath->getKey(), 'code' => $a->admissionPath->code, 'name' => $a->admissionPath->name, 'category' => $a->admissionPath->category->value] : null,
            'form_status' => $a->form_status->value,
            'verification_status' => $a->verification_status->value,
            'selection_status' => $a->selection_status->value,
            'registration_payment_status' => $a->registration_payment_status->value,
            'payment_timing' => $a->config_snapshot['payment_timing'] ?? null,
            'registration_fee' => $a->config_snapshot['registration_fee'] ?? null,
            'registration_invoice_id' => $registrationInvoice?->getKey(),
            'current_stage' => $stage,
            'current_stage_label' => ApplicationStageResolver::label($stage, (array) $this->settings->get('pmb.status_labels')),
            'first_choice' => $a->relationLoaded('choices') ? $a->choices->first()?->studyProgram?->name : null,
            'submitted_at' => $a->submitted_at?->toIso8601String(),
            'revision_due_at' => $a->revision_due_at?->toIso8601String(),
            'has_published_result' => $a->currentDecision?->publication_status?->value === 'published',
            're_registration_status' => $a->reRegistration?->status?->value,
            'created_at' => $a->created_at?->toIso8601String(),
        ];
    }

    /** Kombinasi prodi × sistem kuliah yang ditawarkan jalur ini (kuota is_offered). */
    private function offeredChoices(Application $application): array
    {
        $levels = (array) ($application->config_snapshot['eligible_degree_levels'] ?? []);

        return \Modules\Admission\Models\Quota::query()->with(['studyProgram.faculty', 'studySystem'])
            ->where('period_id', $application->period_id)->where('admission_path_id', $application->admission_path_id)->where('is_offered', true)->get()
            ->filter(fn ($q) => $levels === [] || in_array($q->studyProgram?->degree_level, $levels, true))
            ->map(fn ($q) => [
                'study_program_id' => $q->study_program_id, 'study_program' => $q->studyProgram?->name, 'degree_level' => $q->studyProgram?->degree_level,
                'faculty' => $q->studyProgram?->faculty?->name, 'study_system_id' => $q->study_system_id, 'study_system' => $q->studySystem?->name,
            ])->values()->all();
    }
}
