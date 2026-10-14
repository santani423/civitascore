<?php

namespace Modules\Admission\Controllers\Admin;

use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Enums\PathCategory;
use Modules\Admission\Enums\PaymentTiming;
use Modules\Admission\Enums\QuotaMode;
use Modules\Admission\Models\AdmissionPath;
use Modules\Admission\Models\DocumentRequirement;
use Modules\Admission\Models\DocumentType;
use Modules\Admission\Models\FeeComponent;
use Modules\Admission\Models\FormTemplate;
use Modules\Admission\Models\Period;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\SelectionScheme;
use Modules\Admission\Models\StudySystem;
use Modules\Admission\Models\Wave;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Services\Configuration\MasterDataService;
use Modules\Admission\Services\Configuration\PeriodService;
use Modules\Admission\Support\PmbPermission;
use Modules\Scholarship\Models\Scholarship;

/** Konfigurasi periode, gelombang, jalur, kuota, dokumen, biaya (docs/pmb/03, 11 §A.9). */
class ConfigurationController extends AdminController
{
    public function __construct(private readonly PeriodService $periods, private readonly MasterDataService $master) {}

    // ------------------------------------------------------------------ Lookup gabungan untuk form admin

    public function lookups(Request $request): JsonResponse
    {
        $scope = $this->programScope($request, PmbPermission::CONFIG_READ);

        return ApiResponse::success([
            'academic_terms' => AcademicTerm::query()->orderByDesc('academic_year')->orderByDesc('semester')->get(['id', 'academic_year', 'semester', 'is_current']),
            'faculties' => Faculty::query()->orderBy('name')->get(['id', 'code', 'name']),
            'study_programs' => StudyProgram::query()->when($scope !== null, fn ($q) => $q->whereIn('id', $scope ?: ['-']))
                ->orderBy('name')->get(['id', 'faculty_id', 'code', 'name', 'degree_level', 'is_active']),
            'admission_paths' => AdmissionPath::query()->orderBy('name')->get(['id', 'code', 'name', 'category', 'requires_exam', 'is_active']),
            'study_systems' => StudySystem::query()->orderBy('name')->get(['id', 'code', 'name', 'is_active']),
            'document_types' => DocumentType::query()->orderBy('name')->get(['id', 'code', 'name', 'allows_multiple']),
            'form_templates' => FormTemplate::query()->orderBy('code')->orderByDesc('version')->get(['id', 'code', 'name', 'version', 'status']),
            'selection_schemes' => SelectionScheme::query()->orderBy('code')->orderByDesc('version')->get(['id', 'code', 'name', 'version', 'status']),
            'periods' => Period::query()->orderByDesc('starts_on')->get(['id', 'code', 'name', 'status']),
            'scholarships' => class_exists(Scholarship::class) ? Scholarship::query()->orderBy('name')->get(['id', 'name']) : [],
            'enums' => [
                'path_categories' => PathCategory::values(),
                'payment_timings' => PaymentTiming::values(),
                'quota_modes' => QuotaMode::values(),
                'document_stages' => DocumentStage::values(),
                'content_types' => ContentType::values(),
            ],
        ]);
    }

    /**
     * Staf tenant yang memegang permission PMB tertentu (dropdown penugasan).
     * Hanya slug PMB yang boleh ditanyakan agar endpoint tidak menjadi
     * pemetaan RBAC umum.
     */
    public function staff(Request $request, TenantContext $tenant): JsonResponse
    {
        $data = $request->validate(['permission' => ['required', 'string', Rule::in(PmbPermission::all())]]);

        $userIds = DB::table('user_roles')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'user_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('permissions.slug', $data['permission'])
            ->where('user_roles.university_id', $tenant->universityId())
            ->where(fn ($q) => $q->whereNull('user_roles.expires_at')->orWhere('user_roles.expires_at', '>', now()))
            ->distinct()->pluck('user_roles.user_id');

        return ApiResponse::success(User::query()->whereIn('id', $userIds)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']));
    }

    // ------------------------------------------------------------------ Periode

    public function periods(Request $request): JsonResponse
    {
        $query = Period::query()->with('entryTerm:id,academic_year,semester')->withCount('waves')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->string('search').'%')->orWhere('code', 'like', '%'.$request->string('search').'%')))
            ->orderByDesc('starts_on');

        return $this->paginate($query, $request);
    }

    public function period(Period $period): JsonResponse
    {
        return ApiResponse::success([
            ...$period->load('entryTerm:id,academic_year,semester')->toArray(),
            'has_submitted_applications' => $this->periods->periodHasSubmittedApplications($period),
        ]);
    }

    public function publishChecklist(Period $period): JsonResponse
    {
        $missing = $this->periods->publishChecklist($period);

        return ApiResponse::success(['ready' => $missing === [], 'missing' => $missing]);
    }

    public function storePeriod(Request $request): JsonResponse
    {
        $period = $this->periods->createPeriod($this->validatePeriod($request, true), $this->user($request));

        return ApiResponse::success($period, 'Periode dibuat.', status: 201);
    }

    public function updatePeriod(Request $request, Period $period): JsonResponse
    {
        $data = $this->validatePeriod($request, false);

        return ApiResponse::success($this->periods->updatePeriod($period, $data, $this->user($request), $data['reason'] ?? null), 'Periode diperbarui.');
    }

    public function destroyPeriod(Period $period): JsonResponse
    {
        $this->periods->deletePeriod($period);

        return ApiResponse::success(null, 'Periode dihapus.');
    }

    public function publishPeriod(Request $request, Period $period): JsonResponse
    {
        return ApiResponse::success($this->periods->publishPeriod($period, $this->user($request)), 'Periode dipublikasikan.');
    }

    public function closePeriod(Request $request, Period $period): JsonResponse
    {
        return ApiResponse::success($this->periods->closePeriod($period, $this->user($request)), 'Periode ditutup.');
    }

    public function archivePeriod(Request $request, Period $period): JsonResponse
    {
        return ApiResponse::success($this->periods->archivePeriod($period, $this->user($request)), 'Periode diarsipkan.');
    }

    // ------------------------------------------------------------------ Gelombang

    public function waves(Period $period): JsonResponse
    {
        return ApiResponse::success($period->waves()->withCount('wavePaths')->orderBy('sequence')->get());
    }

    public function storeWave(Request $request, Period $period): JsonResponse
    {
        return ApiResponse::success($this->periods->createWave($period, $this->validateWave($request, true), $this->user($request)), 'Gelombang dibuat.', status: 201);
    }

    public function updateWave(Request $request, Wave $wave): JsonResponse
    {
        $data = $this->validateWave($request, false);

        return ApiResponse::success($this->periods->updateWave($wave, $data, $this->user($request), $data['reason'] ?? null), 'Gelombang diperbarui.');
    }

    public function extendWave(Request $request, Wave $wave): JsonResponse
    {
        $data = $request->validate(['registration_closes_at' => ['required', 'date'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);

        return ApiResponse::success($this->periods->extendWave($wave, $data['registration_closes_at'], $this->user($request), $data['reason']), 'Pendaftaran diperpanjang.');
    }

    public function destroyWave(Wave $wave): JsonResponse
    {
        $this->periods->deleteWave($wave);

        return ApiResponse::success(null, 'Gelombang dihapus.');
    }

    // ------------------------------------------------------------------ Gelombang-jalur

    public function wavePaths(Wave $wave): JsonResponse
    {
        return ApiResponse::success($wave->wavePaths()->with(['admissionPath:id,code,name,requires_exam', 'formTemplate:id,code,name,version,status', 'selectionScheme:id,code,name,version,status'])
            ->withCount(['documentRequirements', 'applications'])->get());
    }

    public function storeWavePath(Request $request, Wave $wave): JsonResponse
    {
        return ApiResponse::success($this->periods->createWavePath($wave, $this->validateWavePath($request, true), $this->user($request)), 'Jalur dibuka pada gelombang.', status: 201);
    }

    public function updateWavePath(Request $request, WavePath $wavePath): JsonResponse
    {
        $data = $this->validateWavePath($request, false);

        return ApiResponse::success($this->periods->updateWavePath($wavePath, $data, $this->user($request), $data['reason'] ?? null), 'Gelombang-jalur diperbarui.');
    }

    public function toggleWavePath(Request $request, WavePath $wavePath): JsonResponse
    {
        $data = $request->validate(['is_open' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);

        return ApiResponse::success($this->periods->toggleWavePath($wavePath, (bool) $data['is_open'], $this->user($request), $data['reason']), $data['is_open'] ? 'Pendaftaran jalur dibuka.' : 'Pendaftaran jalur ditutup.');
    }

    public function destroyWavePath(WavePath $wavePath): JsonResponse
    {
        $this->periods->deleteWavePath($wavePath);

        return ApiResponse::success(null, 'Gelombang-jalur dihapus.');
    }

    // ------------------------------------------------------------------ Master: jalur, sistem kuliah, jenis dokumen

    public function admissionPaths(): JsonResponse
    {
        return ApiResponse::success(AdmissionPath::query()->orderBy('name')->get());
    }

    public function storeAdmissionPath(Request $request): JsonResponse
    {
        return ApiResponse::success($this->master->savePath(null, $this->validatePath($request, null), (string) $this->user($request)->getKey()), 'Jalur dibuat.', status: 201);
    }

    public function updateAdmissionPath(Request $request, AdmissionPath $admissionPath): JsonResponse
    {
        return ApiResponse::success($this->master->savePath($admissionPath, $this->validatePath($request, $admissionPath), (string) $this->user($request)->getKey()), 'Jalur diperbarui.');
    }

    public function destroyAdmissionPath(AdmissionPath $admissionPath): JsonResponse
    {
        $this->master->deletePath($admissionPath);

        return ApiResponse::success(null, 'Jalur dihapus.');
    }

    public function studySystems(): JsonResponse
    {
        return ApiResponse::success(StudySystem::query()->orderBy('name')->get());
    }

    public function storeStudySystem(Request $request): JsonResponse
    {
        return ApiResponse::success($this->master->saveStudySystem(null, $this->validateSystem($request, null)), 'Sistem kuliah dibuat.', status: 201);
    }

    public function updateStudySystem(Request $request, StudySystem $studySystem): JsonResponse
    {
        return ApiResponse::success($this->master->saveStudySystem($studySystem, $this->validateSystem($request, $studySystem)), 'Sistem kuliah diperbarui.');
    }

    public function destroyStudySystem(StudySystem $studySystem): JsonResponse
    {
        $this->master->deleteStudySystem($studySystem);

        return ApiResponse::success(null, 'Sistem kuliah dihapus.');
    }

    public function documentTypes(): JsonResponse
    {
        return ApiResponse::success(DocumentType::query()->orderBy('name')->get());
    }

    public function storeDocumentType(Request $request): JsonResponse
    {
        return ApiResponse::success($this->master->saveDocumentType(null, $this->validateDocumentType($request, null)), 'Jenis dokumen dibuat.', status: 201);
    }

    public function updateDocumentType(Request $request, DocumentType $documentType): JsonResponse
    {
        return ApiResponse::success($this->master->saveDocumentType($documentType, $this->validateDocumentType($request, $documentType)), 'Jenis dokumen diperbarui.');
    }

    public function destroyDocumentType(DocumentType $documentType): JsonResponse
    {
        $this->master->deleteDocumentType($documentType);

        return ApiResponse::success(null, 'Jenis dokumen dihapus.');
    }

    public function documentRequirements(WavePath $wavePath): JsonResponse
    {
        return ApiResponse::success(DocumentRequirement::query()->with('documentType:id,code,name')->where('wave_path_id', $wavePath->getKey())->orderBy('stage')->orderBy('sort_order')->get());
    }

    public function syncDocumentRequirements(Request $request, WavePath $wavePath): JsonResponse
    {
        $data = $request->validate([
            'rows' => ['present', 'array', 'max:100'],
            'rows.*.document_type_id' => ['required', 'string', Rule::exists('pmb_document_types', 'id')->where('university_id', $wavePath->university_id)],
            'rows.*.stage' => ['required', Rule::in(DocumentStage::values())],
            'rows.*.is_mandatory' => ['required', 'boolean'],
            'rows.*.condition' => ['nullable', 'array'],
            'rows.*.instructions' => ['nullable', 'string', 'max:2000'],
            'rows.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $this->periods->syncDocumentRequirements($wavePath, $data['rows'], $this->user($request), $data['reason'] ?? null);

        return $this->documentRequirements($wavePath);
    }

    // ------------------------------------------------------------------ Kuota

    public function quotas(Request $request, Period $period): JsonResponse
    {
        $scope = $this->programScope($request, PmbPermission::CONFIG_READ);

        return ApiResponse::success(Quota::query()->where('period_id', $period->getKey())
            ->when($scope !== null, fn ($q) => $q->whereIn('study_program_id', $scope ?: ['-']))
            ->with(['studyProgram:id,code,name', 'studySystem:id,code,name', 'admissionPath:id,code,name'])->get());
    }

    public function upsertQuotas(Request $request, Period $period): JsonResponse
    {
        $tenant = $period->university_id;
        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:1000'],
            'rows.*.admission_path_id' => ['required', 'string', Rule::exists('pmb_admission_paths', 'id')->where('university_id', $tenant)],
            'rows.*.study_program_id' => ['required', 'string', Rule::exists('study_programs', 'id')->where('university_id', $tenant)],
            'rows.*.study_system_id' => ['required', 'string', Rule::exists('pmb_study_systems', 'id')->where('university_id', $tenant)],
            'rows.*.capacity' => ['required', 'integer', 'min:0', 'max:100000'],
            'rows.*.waitlist_capacity' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'rows.*.min_final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.wave_caps' => ['nullable', 'array'],
            'rows.*.wave_caps.*' => ['integer', 'min:0'],
            'rows.*.is_offered' => ['required', 'boolean'],
            'rows.*.lock_version' => ['nullable', 'integer'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return ApiResponse::success($this->periods->upsertQuotas($period, $data['rows'], $this->user($request), $data['reason'] ?? null), 'Kuota disimpan.');
    }

    public function copyQuotas(Request $request, Period $period, Period $source): JsonResponse
    {
        return ApiResponse::success(['copied' => $this->periods->copyQuotas($period, $source, $this->user($request))], 'Kuota disalin.');
    }

    // ------------------------------------------------------------------ Biaya

    public function feeComponents(Period $period): JsonResponse
    {
        return ApiResponse::success(FeeComponent::query()->where('period_id', $period->getKey())
            ->with(['studyProgram:id,code,name', 'studySystem:id,code,name', 'admissionPath:id,code,name'])->orderBy('sort_order')->get());
    }

    public function upsertFees(Request $request, Period $period): JsonResponse
    {
        $tenant = $period->university_id;
        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:2000'],
            'rows.*.study_program_id' => ['required', 'string', Rule::exists('study_programs', 'id')->where('university_id', $tenant)],
            'rows.*.study_system_id' => ['required', 'string', Rule::exists('pmb_study_systems', 'id')->where('university_id', $tenant)],
            'rows.*.admission_path_id' => ['nullable', 'string', Rule::exists('pmb_admission_paths', 'id')->where('university_id', $tenant)],
            'rows.*.component_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            'rows.*.name' => ['required', 'string', 'max:150'],
            'rows.*.amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'rows.*.is_mandatory_for_enrollment' => ['required', 'boolean'],
            'rows.*.installable' => ['required', 'boolean'],
            'rows.*.installment_plan' => ['nullable', 'array', 'max:12'],
            'rows.*.installment_plan.*.percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'rows.*.installment_plan.*.due_days' => ['required', 'integer', 'min:0', 'max:730'],
            'rows.*.is_published' => ['required', 'boolean'],
            'rows.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $this->periods->upsertFeeComponents($period, $data['rows'], $this->user($request), $data['reason'] ?? null);

        return $this->feeComponents($period);
    }

    public function copyFees(Request $request, Period $period, Period $source): JsonResponse
    {
        return ApiResponse::success(['copied' => $this->periods->copyFeeComponents($period, $source, $this->user($request))], 'Komponen biaya disalin.');
    }

    public function destroyFee(Request $request, FeeComponent $feeComponent): JsonResponse
    {
        $this->periods->deleteFeeComponent($feeComponent, $request->string('reason')->toString() ?: null);

        return ApiResponse::success(null, 'Komponen biaya dihapus.');
    }

    // ------------------------------------------------------------------ Validasi

    /** @return array<string, mixed> */
    private function validatePeriod(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'entry_academic_term_id' => [$req, 'string', Rule::exists('academic_terms', 'id')->where('university_id', app(TenantContext::class)->universityId())],
            'code' => [$req, 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            'name' => [$req, 'string', 'max:150'],
            'starts_on' => [$req, 'date'],
            'ends_on' => [$req, 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateWave(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'code' => [$req, 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            'name' => [$req, 'string', 'max:150'],
            'sequence' => [$req, 'integer', 'min:1', 'max:99'],
            'registration_opens_at' => [$req, 'date'],
            'registration_closes_at' => [$req, 'date'],
            'payment_deadline_at' => ['nullable', 'date'],
            'form_lock_at' => ['nullable', 'date'],
            'exam_starts_on' => ['nullable', 'date'],
            'exam_ends_on' => ['nullable', 'date'],
            'announcement_at' => [$req, 'date'],
            're_registration_opens_at' => [$req, 'date'],
            're_registration_closes_at' => [$req, 'date'],
            'is_ods' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateWavePath(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';
        $tenant = app(TenantContext::class)->universityId();

        return $request->validate([
            'admission_path_id' => [$creating ? 'required' : 'prohibited', 'string', Rule::exists('pmb_admission_paths', 'id')->where('university_id', $tenant)],
            'registration_fee' => [$req, 'numeric', 'min:0', 'max:999999999'],
            'payment_timing' => [$req, Rule::in(PaymentTiming::values())],
            'max_choices' => [$req, 'integer', 'min:1', 'max:5'],
            'allow_cross_system_choice' => ['sometimes', 'boolean'],
            'form_template_id' => [$req, 'string', Rule::exists('pmb_form_templates', 'id')->where('university_id', $tenant)],
            'selection_scheme_id' => ['nullable', 'string', Rule::exists('pmb_selection_schemes', 'id')->where('university_id', $tenant)],
            'quota_mode' => [$req, Rule::in(QuotaMode::values())],
            'bulk_verifiable' => ['sometimes', 'boolean'],
            'is_open' => ['sometimes', 'boolean'],
            'public_notes' => ['nullable', 'string', 'max:5000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validatePath(Request $request, ?AdmissionPath $current): array
    {
        $req = $current ? 'sometimes' : 'required';

        return $request->validate([
            'code' => [$req, 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('pmb_admission_paths', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($current?->getKey())],
            'name' => [$req, 'string', 'max:150'],
            'category' => [$req, Rule::in(PathCategory::values())],
            'description' => ['nullable', 'string', 'max:5000'],
            'eligible_degree_levels' => ['nullable', 'array'],
            'eligible_degree_levels.*' => ['string', 'max:10'],
            'requires_exam' => ['sometimes', 'boolean'],
            'requires_previous_institution' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateSystem(Request $request, ?StudySystem $current): array
    {
        $req = $current ? 'sometimes' : 'required';

        return $request->validate([
            'code' => [$req, 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('pmb_study_systems', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($current?->getKey())],
            'name' => [$req, 'string', 'max:100'],
            'schedule_note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateDocumentType(Request $request, ?DocumentType $current): array
    {
        $req = $current ? 'sometimes' : 'required';

        return $request->validate([
            'code' => [$req, 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('pmb_document_types', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($current?->getKey())],
            'name' => [$req, 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'allowed_mime_types' => [$req, 'array', 'min:1'],
            'allowed_mime_types.*' => [Rule::in(['image/jpeg', 'image/png', 'application/pdf'])],
            'max_size_kb' => [$req, 'integer', 'min:50', 'max:10240'],
            'allows_multiple' => ['sometimes', 'boolean'],
            'requires_original_check' => ['sometimes', 'boolean'],
            'is_sensitive' => ['sometimes', 'boolean'],
            'retention_class' => ['nullable', 'string', 'max:30'],
        ]);
    }
}
