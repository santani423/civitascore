<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Models\DocumentRequirement;
use Modules\Admission\Services\Application\ApplicationService;
use Modules\Admission\Services\Application\DocumentService;
use Modules\Admission\Support\DocumentReasonCodes;
use Modules\Admission\Support\FormRuleEvaluator;

/** Dokumen persyaratan pendaftar (docs/pmb/11 §A.6, 04 §E). */
class DocumentController extends ApplicantController
{
    public function __construct(private readonly DocumentService $documents) {}

    public function index(Request $request, string $id, ApplicationService $applications): JsonResponse
    {
        $application = $this->application($request, $id);
        $stage = DocumentStage::tryFrom((string) $request->query('stage', 'application')) ?? DocumentStage::Application;
        $context = $applications->ruleContext($application);
        $requirements = DocumentRequirement::query()->where('wave_path_id', $application->wave_path_id)->where('stage', $stage->value)->get()->keyBy('document_type_id');

        $docs = ApplicationDocument::query()->with(['documentType', 'currentVersion.fileUpload', 'versions.reviews'])
            ->where('application_id', $application->getKey())->where('stage', $stage->value)->orderBy('slot')->get()
            ->filter(fn ($d) => FormRuleEvaluator::passes($requirements->get($d->document_type_id)?->condition, $context) || $d->current_version_id !== null)
            ->map(function (ApplicationDocument $d) use ($requirements) {
                $lastReview = $d->versions->flatMap->reviews->sortByDesc('created_at')->first();
                $masked = $lastReview && in_array($lastReview->reason_code, DocumentReasonCodes::MASKED_FOR_APPLICANT, true);

                return [
                    'id' => $d->getKey(),
                    'document_type' => $d->documentType?->only(['id', 'code', 'name', 'description', 'allowed_mime_types', 'max_size_kb', 'allows_multiple']),
                    'stage' => $d->stage->value, 'slot' => $d->slot, 'is_mandatory' => $d->is_mandatory, 'status' => $d->status->value,
                    'instructions' => $requirements->get($d->document_type_id)?->instructions,
                    'locked' => $d->locked_at !== null,
                    'current_version' => $d->currentVersion ? ['version_no' => $d->currentVersion->version_no, 'scan_status' => $d->currentVersion->fileUpload?->status?->value, 'original_name' => $d->currentVersion->fileUpload?->original_name, 'uploaded_at' => $d->currentVersion->created_at?->toIso8601String()] : null,
                    'versions_count' => $d->versions->count(),
                    'last_review' => $lastReview ? ['decision' => $lastReview->decision->value, 'reason_code' => $masked ? null : $lastReview->reason_code, 'note' => $masked ? DocumentReasonCodes::MASKED_MESSAGE : $lastReview->note_for_applicant] : null,
                ];
            })->values();

        return ApiResponse::success($docs);
    }

    public function addSlot(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['document_type_id' => ['required', 'string', 'size:26'], 'stage' => ['nullable', 'in:application,re_registration']]);
        $document = $this->documents->addSlot($this->application($request, $id), $data['document_type_id'], DocumentStage::from($data['stage'] ?? 'application'));

        return ApiResponse::success(['id' => $document->getKey(), 'slot' => $document->slot], 'Slot ditambahkan.', status: 201);
    }

    public function upload(Request $request, string $id, string $documentId): JsonResponse
    {
        $application = $this->application($request, $id);
        $document = ApplicationDocument::query()->where('application_id', $application->getKey())->whereKey($documentId)->firstOrFail();
        $data = $request->validate(['file' => ['required', 'file', 'max:20480'], 'achievement_id' => ['nullable', 'string', 'size:26']]);
        $version = $this->documents->upload($application, $document, $request->file('file'), $this->actor($request));

        if (! empty($data['achievement_id'])) {
            $this->account($request)->achievements()->whereKey($data['achievement_id'])->firstOrFail()->forceFill(['application_document_id' => $document->getKey()])->save();
        }

        return ApiResponse::success([
            'version_no' => $version->version_no,
            'status' => $document->refresh()->status->value,
            'scan_status' => $version->fileUpload?->status?->value,
        ], 'Dokumen diunggah.', status: 201);
    }

    public function download(Request $request, string $id, string $documentId): JsonResponse
    {
        $document = ApplicationDocument::query()->where('application_id', $this->application($request, $id)->getKey())->whereKey($documentId)->with('currentVersion.fileUpload')->firstOrFail();

        return ApiResponse::success(['url' => $this->documents->applicantDownloadUrl($document), 'expires_at' => now()->addMinutes((int) config('pmb.download_url_ttl_minutes', 5))->toIso8601String()]);
    }
}
