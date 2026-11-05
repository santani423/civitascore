<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Enums\AssessmentDomain;
use Modules\Admission\Enums\AssessmentPurpose;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AssessmentDimension;
use Modules\Admission\Models\AssessmentSession;
use Modules\Admission\Models\AssessmentType;
use Modules\Admission\Models\AssessmentVersion;
use Modules\Admission\Services\Assessment\AssessmentApprovalService;
use Modules\Admission\Services\Assessment\AssessmentConfigurationService;
use Modules\Admission\Services\Assessment\AssessmentMonitoringService;

/** Konfigurasi One Atma Talent: jenis, versi, dimensi (docs/one-atma-talent/03–05). Bank soal ada di AssessmentQuestionController. */
class AssessmentController extends AdminController
{
    public function __construct(
        private readonly AssessmentConfigurationService $config,
        private readonly AssessmentApprovalService $approvals,
        private readonly AssessmentMonitoringService $monitoring,
    ) {}

    public function types(Request $request): JsonResponse
    {
        $types = AssessmentType::query()->withCount('versions')->orderBy('name')->get();

        return ApiResponse::success($types->map(fn (AssessmentType $t) => [
            ...$this->typeRow($t),
            'versions_count' => $t->versions_count,
            'open_version' => $this->openVersion($t),
            'published_version' => $this->publishedVersion($t),
            'sessions_count' => AssessmentSession::query()->whereHas('version', fn ($v) => $v->where('assessment_type_id', $t->getKey()))->count(),
        ])->values());
    }

    public function storeType(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('pmb_assessment_types', 'code')->where('university_id', $this->tenantId())],
            'name' => ['required', 'string', 'max:150'],
            'domain' => ['required', Rule::enum(AssessmentDomain::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return ApiResponse::success($this->typeRow($this->config->createType($data, $this->user($request))), 'Jenis asesmen dibuat.', status: 201);
    }

    /** Ubah nama, deskripsi, atau status aktif. Setiap perubahan dicatat dengan alasan opsional. */
    public function updateType(Request $request, AssessmentType $type): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return ApiResponse::success($this->typeRow($this->config->updateType($type, $data)), 'Jenis asesmen diperbarui.');
    }

    public function versions(AssessmentType $type): JsonResponse
    {
        $versions = $type->versions()->withCount(['dimensions', 'items', 'sessions'])->orderByDesc('version_no')->get();

        return ApiResponse::success($versions->map(fn (AssessmentVersion $v) => $this->versionRow($v)));
    }

    public function storeVersion(Request $request, AssessmentType $type): JsonResponse
    {
        $data = $this->validateVersion($request, required: true);
        $version = $this->config->createVersion($type, $data, $this->user($request));

        return ApiResponse::success($this->versionRow($version), 'Versi draft dibuat.', status: 201);
    }

    /** Salin versi (dimensi dan butir) menjadi draft baru. Versi asal tidak berubah. */
    public function cloneVersion(Request $request, AssessmentVersion $version): JsonResponse
    {
        $data = $request->validate(['change_note' => ['nullable', 'string', 'max:1000']]);
        $copy = $this->config->cloneVersion($version, $this->user($request), $data['change_note'] ?? null);

        return ApiResponse::success($this->versionRow($copy), 'Versi baru dibuat dari salinan.', status: 201);
    }

    public function showVersion(Request $request, AssessmentVersion $version): JsonResponse
    {
        $version->load(['type', 'dimensions', 'approvalRequest.workflow', 'approvalRequest.steps.workflowStep']);
        $sessions = AssessmentSession::query()->where('version_id', $version->getKey())
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return ApiResponse::success([
            ...$this->versionRow($version),
            'type' => ['id' => $version->type->getKey(), 'code' => $version->type->code, 'name' => $version->type->name, 'domain' => $version->type->domain->value],
            'instructions' => $version->instructions,
            'change_note' => $version->change_note,
            'scoring_config' => $version->scoring_config,
            'parent_version_id' => $version->parent_version_id,
            'period_id' => $version->period_id,
            'duration_minutes' => $version->duration_minutes,
            'available_from' => $version->available_from?->toIso8601String(),
            'available_until' => $version->available_until?->toIso8601String(),
            'dimensions' => $version->dimensions->map(fn (AssessmentDimension $d) => [
                'id' => $d->getKey(), 'code' => $d->code, 'name' => $d->name, 'description' => $d->description,
                'interpretation' => $d->interpretation, 'sort_order' => $d->sort_order, 'items_count' => $d->items()->count(),
            ])->values(),
            'sessions_by_status' => $sessions,
            'readiness' => $this->readiness($version),
            'approval' => $this->versionApproval($version),
            'history' => $this->monitoring->auditQuery(['auditable_id' => $version->getKey()])->limit(50)->get()
                ->map(fn ($log) => ['action' => $log->action->value, 'user' => $log->user?->name, 'old' => $log->old_values, 'new' => $log->new_values, 'reason' => $log->reason, 'at' => $log->created_at->toIso8601String()])->values(),
            'can_edit' => $version->status->isEditable(),
            'can_delete' => $version->status->isEditable() && $version->submitted_at === null && ! $version->sessions()->exists(),
        ]);
    }

    /** Perbedaan antara dua versi jenis yang sama (dimensi dan butir). */
    public function diff(AssessmentVersion $version, AssessmentVersion $other): JsonResponse
    {
        if ($version->assessment_type_id !== $other->assessment_type_id) {
            throw new DomainRuleException('Perbandingan hanya untuk versi dari jenis asesmen yang sama.', 'TYPE_MISMATCH', 422);
        }

        return ApiResponse::success([
            'from' => ['id' => $version->getKey(), 'version_no' => $version->version_no, 'status' => $version->status->value],
            'to' => ['id' => $other->getKey(), 'version_no' => $other->version_no, 'status' => $other->status->value],
            ...$this->config->diff($version, $other),
        ]);
    }

    public function updateVersion(Request $request, AssessmentVersion $version): JsonResponse
    {
        $data = $this->validateVersion($request, required: false);

        return ApiResponse::success($this->versionRow($this->config->updateVersion($version, $data)), 'Versi diperbarui.');
    }

    /** Ajukan versi draft untuk ditelaah. Pemeriksaan kelengkapan dijalankan sebelum approval dibuat. */
    public function submitVersion(Request $request, AssessmentVersion $version): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $version = $this->approvals->submit($version, $this->user($request), $data['note'] ?? null);

        return ApiResponse::success($this->versionRow($version), 'Versi diajukan untuk ditelaah.');
    }

    /** Keputusan pada langkah persetujuan aktif. Alasan wajib untuk tolak dan kembalikan. */
    public function decideVersion(Request $request, AssessmentVersion $version): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(AssessmentApprovalService::DECISIONS)],
            'comment' => ['required_unless:decision,approve', 'nullable', 'string', 'min:5', 'max:1000'],
        ]);

        $version = $this->approvals->decide($version, $this->user($request), $data['decision'], $data['comment'] ?? null);

        return ApiResponse::success($this->versionRow($version), 'Keputusan persetujuan dicatat.');
    }

    public function publishVersion(Request $request, AssessmentVersion $version): JsonResponse
    {
        $version = $this->config->publish($version, $this->user($request));

        return ApiResponse::success($this->versionRow($version), 'Versi diterbitkan.');
    }

    public function archiveVersion(Request $request, AssessmentVersion $version): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        return ApiResponse::success($this->versionRow($this->config->archive($version, $data['reason'])), 'Versi diarsipkan.');
    }

    public function destroyVersion(Request $request, AssessmentVersion $version): JsonResponse
    {
        $this->config->deleteDraft($version);

        return response()->json(null, 204);
    }

    public function storeDimension(Request $request, AssessmentVersion $version): JsonResponse
    {
        $data = $request->validate($this->dimensionRules(required: true));

        return ApiResponse::success($this->config->addDimension($version, $data), 'Dimensi ditambahkan.', status: 201);
    }

    public function updateDimension(Request $request, AssessmentDimension $dimension): JsonResponse
    {
        $data = $request->validate($this->dimensionRules(required: false));

        return ApiResponse::success($this->config->updateDimension($dimension, $data), 'Dimensi diperbarui.');
    }

    public function destroyDimension(AssessmentDimension $dimension): JsonResponse
    {
        $this->config->deleteDimension($dimension);

        return response()->json(null, 204);
    }

    /** @return array<string, mixed> */
    private function validateVersion(Request $request, bool $required): array
    {
        $p = $required ? 'required' : 'sometimes';

        return $request->validate([
            'purpose' => [$required ? 'required' : 'prohibited', Rule::enum(AssessmentPurpose::class)],
            'title' => [$p, 'string', 'max:150'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
            'period_id' => ['nullable', 'string', 'size:26', Rule::exists('pmb_periods', 'id')->where('university_id', $this->tenantId())],
        ]);
    }

    /** @return array<string, mixed> */
    private function dimensionRules(bool $required): array
    {
        $p = $required ? 'required' : 'sometimes';

        return [
            'code' => [$p, 'string', 'max:10'],
            'name' => [$p, 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'interpretation' => ['nullable', 'string', 'max:4000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ];
    }

    /** @return array<string, mixed> */
    private function typeRow(AssessmentType $type): array
    {
        return [
            'id' => $type->getKey(),
            'code' => $type->code,
            'name' => $type->name,
            'domain' => $type->domain->value,
            'domain_supports_scoring' => $type->domain->supportsScoring(),
            'description' => $type->description,
            'is_active' => $type->is_active,
        ];
    }

    /** @return array<string, mixed> */
    private function versionRow(AssessmentVersion $version): array
    {
        return [
            'id' => $version->getKey(),
            'version_no' => $version->version_no,
            'title' => $version->title,
            'purpose' => $version->purpose->value,
            'status' => $version->status->value,
            'dimensions_count' => $version->dimensions_count ?? $version->dimensions()->count(),
            'items_count' => $version->items_count ?? $version->items()->count(),
            'sessions_count' => $version->sessions_count ?? $version->sessions()->count(),
            'submitted_at' => $version->submitted_at?->toIso8601String(),
            'approved_at' => $version->approved_at?->toIso8601String(),
            'published_at' => $version->published_at?->toIso8601String(),
            'archived_at' => $version->archived_at?->toIso8601String(),
            'assessment_type_id' => $version->assessment_type_id,
        ];
    }

    /** @return array<string, mixed>|null */
    private function openVersion(AssessmentType $type): ?array
    {
        $open = $type->versions()->whereIn('status', AssessmentConfigurationService::OPEN_STATUSES)->orderByDesc('version_no')->first();

        return $open === null ? null : ['id' => $open->getKey(), 'version_no' => $open->version_no, 'status' => $open->status->value];
    }

    /** @return array<string, mixed>|null */
    private function publishedVersion(AssessmentType $type): ?array
    {
        $published = $type->versions()->where('status', 'published')->first();

        return $published === null ? null : ['id' => $published->getKey(), 'version_no' => $published->version_no, 'title' => $published->title];
    }

    /**
     * Pemeriksaan kelengkapan tanpa mengubah apa pun (untuk panduan di UI).
     *
     * @return array<string, mixed>
     */
    private function readiness(AssessmentVersion $version): array
    {
        if (! $version->status->isEditable()) {
            return ['ok' => false, 'code' => null, 'message' => 'Versi sudah diajukan atau diterbitkan.'];
        }

        try {
            $this->config->assertReadyForReview($version);

            return ['ok' => true, 'code' => null, 'message' => 'Siap diajukan untuk ditelaah.'];
        } catch (DomainRuleException $e) {
            return ['ok' => false, 'code' => $e->errorCode, 'message' => $e->getMessage()];
        }
    }

    /** @return array<string, mixed>|null */
    private function versionApproval(AssessmentVersion $version): ?array
    {
        $request = $version->approvalRequest;

        if ($request === null) {
            return null;
        }

        return [
            'request_id' => $request->getKey(),
            'status' => $request->status->value,
            'current_step' => $request->currentStep?->workflowStep->name,
            'steps' => $request->steps->sortBy('sequence')->values()->map(fn ($step) => [
                'sequence' => $step->sequence,
                'name' => $step->workflowStep->name,
                'status' => $step->status->value,
                'acted_at' => $step->acted_at?->toIso8601String(),
            ]),
            'submitted_at' => $request->submitted_at?->toIso8601String(),
            'completed_at' => $request->completed_at?->toIso8601String(),
            'can_decide' => $request->currentStep !== null && $version->status->value === 'in_review',
        ];
    }

    private function tenantId(): string
    {
        return (string) app(TenantContext::class)->universityId();
    }
}
