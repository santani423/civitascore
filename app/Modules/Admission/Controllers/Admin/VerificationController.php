<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Enums\AssignmentStatus;
use Modules\Admission\Enums\DuplicateFlagStatus;
use Modules\Admission\Enums\ReviewDecision;
use Modules\Admission\Enums\VerificationStatus;
use Modules\Admission\Models\AccountRecoveryRequest;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Models\DocumentVersion;
use Modules\Admission\Models\DuplicateFlag;
use Modules\Admission\Models\VerificationAssignment;
use Modules\Admission\Services\Account\AccountService;
use Modules\Admission\Services\Application\DocumentService;
use Modules\Admission\Services\Verification\VerificationService;
use Modules\Admission\Support\DocumentReasonCodes;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\ScopeResolver;
use Modules\Admission\Support\SensitiveAccessLogger;

/** Antrean & keputusan verifikasi administrasi (FR-VER, ADM-23/24/25). */
class VerificationController extends AdminController
{
    public function __construct(private readonly VerificationService $verification, private readonly DocumentService $documents) {}

    public function stats(Request $request, ScopeResolver $scope): JsonResponse
    {
        $user = $this->user($request);
        $base = fn () => $scope->constrainApplications(Application::query(), $user, PmbPermission::VERIFICATION_DECIDE);

        return ApiResponse::success([
            'queue' => $this->verification->scopeQueueable($base())->whereDoesntHave('activeAssignment')->count(),
            'in_review' => $base()->where('verification_status', VerificationStatus::InReview->value)->count(),
            'mine' => VerificationAssignment::query()->where('reviewer_user_id', $user->getKey())->where('status', AssignmentStatus::Active->value)->count(),
            'revision_requested' => $base()->where('verification_status', VerificationStatus::RevisionRequested->value)->count(),
            'verified_today' => $base()->where('verification_status', VerificationStatus::Verified->value)->where('verified_at', '>=', now()->startOfDay())->count(),
            'rejected' => $base()->where('verification_status', VerificationStatus::Rejected->value)->count(),
            'open_duplicate_flags' => DuplicateFlag::query()->where('status', DuplicateFlagStatus::Open->value)->count(),
            'my_assignments' => VerificationAssignment::query()->with('application:id,registration_number,applicant_account_id,submitted_at', 'application.account:id,name')
                ->where('reviewer_user_id', $user->getKey())->where('status', AssignmentStatus::Active->value)->get()
                ->map(fn ($a) => ['application_id' => $a->application_id, 'registration_number' => $a->application?->registration_number, 'name' => $a->application?->account?->name, 'claimed_until' => $a->claimed_until?->toIso8601String()]),
            'reason_codes' => ['document' => DocumentReasonCodes::DOCUMENT, 'application_rejection' => DocumentReasonCodes::APPLICATION_REJECTION],
        ]);
    }

    public function claimNext(Request $request): JsonResponse
    {
        $filters = $request->validate(['wave_id' => ['nullable', 'string'], 'wave_path_id' => ['nullable', 'string'], 'admission_path_id' => ['nullable', 'string']]);
        $application = $this->verification->claimNext($this->user($request), $filters);

        return $application
            ? ApiResponse::success(['application_id' => $application->getKey()], 'Aplikasi diklaim.')
            : ApiResponse::success(null, 'Antrean kosong.');
    }

    public function claim(Request $request, Application $application): JsonResponse
    {
        $this->verification->claim($application, $this->user($request));

        return ApiResponse::success(['application_id' => $application->getKey()], 'Aplikasi diklaim.');
    }

    public function release(Request $request, Application $application): JsonResponse
    {
        $this->verification->release($application, $this->user($request));

        return ApiResponse::success(null, 'Klaim dilepas.');
    }

    public function assign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'application_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'application_ids.*' => ['string', 'size:26'],
            'reviewer_ids' => ['required', 'array', 'min:1', 'max:100'],
            'reviewer_ids.*' => ['string'],
            'strategy' => ['required', Rule::in(['round_robin', 'by_path'])],
        ]);

        return ApiResponse::success($this->verification->assign($data['application_ids'], $data['reviewer_ids'], $data['strategy'], $this->user($request)), 'Penugasan disimpan.');
    }

    public function verify(Request $request, Application $application): JsonResponse
    {
        $data = $request->validate(['lock_version' => ['required', 'integer'], 'note' => ['nullable', 'string', 'max:2000']]);
        $updated = $this->verification->verify($application, (int) $data['lock_version'], $data['note'] ?? null, $this->user($request));

        return ApiResponse::success(['verification_status' => $updated->verification_status->value], 'Aplikasi terverifikasi.');
    }

    public function requestRevision(Request $request, Application $application): JsonResponse
    {
        $data = $request->validate([
            'targets' => ['required', 'array'],
            'targets.fields' => ['nullable', 'array'],
            'targets.fields.*' => ['string', 'max:60'],
            'targets.documents' => ['nullable', 'array'],
            'targets.documents.*' => ['string', 'max:30'],
            'note_for_applicant' => ['required', 'string', 'min:5', 'max:2000'],
            'due_at' => ['nullable', 'date', 'after:now'],
            'lock_version' => ['required', 'integer'],
        ]);
        $updated = $this->verification->requestRevision($application, $data['targets'], $data['note_for_applicant'], $data['due_at'] ?? null, (int) $data['lock_version'], $this->user($request));

        return ApiResponse::success(['verification_status' => $updated->verification_status->value, 'revision_due_at' => $updated->revision_due_at?->toIso8601String()], 'Permintaan perbaikan dikirim.');
    }

    public function reject(Request $request, Application $application): JsonResponse
    {
        $data = $request->validate([
            'reason_code' => ['required', Rule::in(DocumentReasonCodes::APPLICATION_REJECTION)],
            'note_for_applicant' => ['required', 'string', 'min:5', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'lock_version' => ['required', 'integer'],
        ]);
        $updated = $this->verification->reject($application, $data['reason_code'], $data['note_for_applicant'], $data['internal_note'] ?? null, (int) $data['lock_version'], $this->user($request));

        return ApiResponse::success(['verification_status' => $updated->verification_status->value], 'Aplikasi ditolak.');
    }

    public function reopen(Request $request, Application $application): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $updated = $this->verification->reopen($application, $data['reason'], $this->user($request));

        return ApiResponse::success(['verification_status' => $updated->verification_status->value], 'Keputusan verifikasi dibuka kembali.');
    }

    public function declareConflict(Request $request, Application $application): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']]);
        $this->verification->declareConflict($application, $this->user($request), $data['reason']);

        return ApiResponse::success(null, 'Konflik kepentingan dicatat; aplikasi dilepas dari Anda.', status: 201);
    }

    public function bulkVerify(Request $request): JsonResponse
    {
        $data = $request->validate(['application_ids' => ['required', 'array', 'min:1', 'max:200'], 'application_ids.*' => ['string', 'size:26']]);

        return ApiResponse::success($this->verification->bulkVerify($data['application_ids'], $this->user($request)), 'Verifikasi massal selesai.');
    }

    public function bulkPhotos(Request $request): JsonResponse
    {
        $data = $request->validate(['document_ids' => ['required', 'array', 'min:1', 'max:200'], 'document_ids.*' => ['string', 'size:26']]);

        return ApiResponse::success($this->verification->bulkApprovePhotos($data['document_ids'], $this->user($request)), 'Foto disetujui.');
    }

    /** Dokumen per versi: hanya lewat URL bertanda tangan + log akses untuk dokumen sensitif. */
    public function downloadVersion(Request $request, DocumentVersion $documentVersion): JsonResponse
    {
        $application = $documentVersion->document->application;
        $this->assertApplicationScope($request, PmbPermission::DOCUMENTS_DOWNLOAD, $application);
        $purpose = $request->string('purpose', 'verifikasi dokumen')->toString();

        return ApiResponse::success(['url' => $this->documents->staffDownloadUrl($documentVersion, $this->user($request), mb_substr($purpose, 0, 255), $request->ip())])->header('Cache-Control', 'no-store');
    }

    public function reviewDocument(Request $request, ApplicationDocument $applicationDocument): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(ReviewDecision::values())],
            'version_id' => ['required_unless:decision,reopened', 'nullable', 'string', 'size:26'],
            'reason_code' => ['nullable', Rule::in(DocumentReasonCodes::DOCUMENT)],
            'note_for_applicant' => ['nullable', 'string', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $permission = $applicationDocument->stage->value === 're_registration' ? PmbPermission::REREG_DECIDE : PmbPermission::VERIFICATION_DECIDE;
        abort_unless($this->user($request)->hasPermissionTo($permission), 403);
        $this->assertApplicationScope($request, $permission, $applicationDocument->application_id);

        if ($data['decision'] === ReviewDecision::Reopened->value) {
            abort_unless($this->user($request)->hasPermissionTo(PmbPermission::VERIFICATION_OVERRIDE), 403);
            $document = $this->documents->reopen($applicationDocument, (string) ($data['internal_note'] ?? $data['note_for_applicant'] ?? ''), $this->user($request));
        } else {
            $document = $this->documents->review($applicationDocument, $data, $this->user($request));
        }

        return ApiResponse::success(['id' => $document->getKey(), 'status' => $document->status->value], 'Dokumen ditinjau.');
    }

    public function duplicateFlags(Request $request): JsonResponse
    {
        $query = DuplicateFlag::query()->with(['account:id,name,email,phone,created_at', 'relatedAccount:id,name,email,phone,created_at', 'application:id,registration_number'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', DuplicateFlagStatus::Open->value))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (DuplicateFlag $f) => [
            'id' => $f->getKey(), 'type' => $f->type->value, 'status' => $f->status->value, 'similarity_score' => $f->similarity_score, 'note' => $f->note,
            'account' => $f->account?->only(['id', 'name', 'email', 'created_at']),
            'related_account' => $f->relatedAccount?->only(['id', 'name', 'email', 'created_at']),
            'application' => $f->application?->only(['id', 'registration_number']),
            'resolution_note' => $f->resolution_note, 'resolved_at' => $f->resolved_at?->toIso8601String(), 'created_at' => $f->created_at?->toIso8601String(),
        ]);
    }

    public function resolveFlag(Request $request, DuplicateFlag $duplicateFlag): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([DuplicateFlagStatus::ConfirmedDuplicate->value, DuplicateFlagStatus::NotDuplicate->value, DuplicateFlagStatus::Merged->value])],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
            'secondary_account_id' => ['nullable', 'string', 'size:26'],
        ]);
        $flag = $this->verification->resolveDuplicateFlag($duplicateFlag, $data['status'], $data['note'], $this->user($request), $data['secondary_account_id'] ?? null);

        return ApiResponse::success(['id' => $flag->getKey(), 'status' => $flag->status->value], 'Penanda duplikat diselesaikan.');
    }

    public function recoveryRequests(Request $request, PmbFileStorage $storage): JsonResponse
    {
        $query = AccountRecoveryRequest::query()->with(['account:id,name,email', 'identityFile'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', 'pending'))
            ->orderBy('created_at');

        return $this->paginate($query, $request, fn (AccountRecoveryRequest $r) => [
            'id' => $r->getKey(), 'status' => $r->status->value, 'full_name' => $r->full_name, 'birth_date' => $r->birth_date?->toDateString(),
            'new_email' => $r->new_email, 'account' => $r->account?->only(['id', 'name', 'email']),
            'matches_account' => $r->applicant_account_id !== null,
            'identity_file_available' => $r->identityFile !== null && $r->identityFile->status->value === 'clean',
            'resolution_note' => $r->resolution_note, 'created_at' => $r->created_at?->toIso8601String(),
        ]);
    }

    public function decideRecovery(Request $request, AccountRecoveryRequest $accountRecoveryRequest, AccountService $accounts, PmbFileStorage $storage, SensitiveAccessLogger $logger): JsonResponse
    {
        if ($request->boolean('view_identity')) {
            abort_if($accountRecoveryRequest->identityFile === null, 404);
            $logger->log($this->user($request), $accountRecoveryRequest, 'identity_file', 'verifikasi pemulihan akun', $request->ip());

            return ApiResponse::success(['url' => $storage->temporaryUrl($accountRecoveryRequest->identityFile)])->header('Cache-Control', 'no-store');
        }

        $data = $request->validate(['approve' => ['required', 'boolean'], 'note' => ['required', 'string', 'min:5', 'max:1000']]);
        $resolved = $accounts->resolveRecoveryRequest($accountRecoveryRequest, (bool) $data['approve'], $data['note'], $this->user($request));

        return ApiResponse::success(['id' => $resolved->getKey(), 'status' => $resolved->status->value], 'Permintaan pemulihan diputuskan.');
    }

    /** Galeri pas foto (PHOTO) yang menunggu persetujuan untuk tinjauan massal (FR-VER-05 a). */
    public function photoQueue(Request $request): JsonResponse
    {
        $query = ApplicationDocument::query()->with(['application:id,registration_number,applicant_account_id', 'application.account:id,name', 'currentVersion.fileUpload'])
            ->whereHas('documentType', fn ($q) => $q->where('code', 'PHOTO'))
            ->whereIn('status', ['uploaded', 'in_review'])
            ->whereIn('application_id', app(ScopeResolver::class)->constrainApplications(Application::query()->select('id'), $this->user($request), PmbPermission::VERIFICATION_DECIDE));
        $storage = app(PmbFileStorage::class);

        return $this->paginate($query, $request, fn (ApplicationDocument $d) => [
            'id' => $d->getKey(), 'registration_number' => $d->application?->registration_number, 'name' => $d->application?->account?->name,
            'url' => $d->currentVersion?->fileUpload && $d->currentVersion->fileUpload->status->value === 'clean' ? $storage->temporaryUrl($d->currentVersion->fileUpload, 30) : null,
        ], 60);
    }
}
