<?php

namespace Modules\Admission\Services\Application;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Enums\DocumentStatus;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\ReRegistrationStatus;
use Modules\Admission\Enums\ReviewDecision;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Models\DocumentRequirement;
use Modules\Admission\Models\DocumentReview;
use Modules\Admission\Models\DocumentType;
use Modules\Admission\Models\DocumentVersion;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\DocumentReasonCodes;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\SensitiveAccessLogger;
use Modules\Admission\Support\StateTransition;
use Modules\FileManagement\Enums\FileUploadStatus;

/**
 * Pengelolaan dokumen persyaratan (docs/pmb/04 §E, SM-03): unggah per versi
 * (BR-21), validasi isi berkas, penguncian, keputusan per dokumen dengan
 * kode alasan, dan unduhan bertanda tangan dengan pemeriksaan hak akses.
 */
class DocumentService
{
    /** Aturan foto 04 FR-DOC-02: ≥ 400×600 px, rasio 3:4 ± 10%. */
    private const PHOTO_RULES = ['min_width' => 400, 'min_height' => 600, 'aspect' => 0.75];

    public function __construct(
        private readonly PmbFileStorage $storage,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly SensitiveAccessLogger $sensitiveLog,
    ) {}

    /**
     * Membuat baris dokumen (status `missing`) dari persyaratan gelombang-jalur
     * untuk satu tahap. Idempoten.
     */
    public function syncRequirements(Application $application, DocumentStage $stage): void
    {
        $requirements = DocumentRequirement::query()->where('wave_path_id', $application->wave_path_id)->where('stage', $stage->value)->get();

        foreach ($requirements as $requirement) {
            ApplicationDocument::query()->firstOrCreate(
                ['application_id' => $application->getKey(), 'document_type_id' => $requirement->document_type_id, 'stage' => $stage->value, 'slot' => 1],
                ['university_id' => $application->university_id, 'is_mandatory' => $requirement->is_mandatory, 'status' => DocumentStatus::Missing],
            );
        }
    }

    /** Slot tambahan untuk jenis dokumen `allows_multiple` (mis. sertifikat prestasi). */
    public function addSlot(Application $application, string $documentTypeId, DocumentStage $stage): ApplicationDocument
    {
        $type = DocumentType::query()->findOrFail($documentTypeId);

        if (! $type->allows_multiple) {
            throw new DomainRuleException('Jenis dokumen ini hanya satu berkas.', 'SINGLE_DOCUMENT_ONLY');
        }

        $this->assertApplicantCanUpload($application, null, $stage);

        $slot = (int) ApplicationDocument::query()->where('application_id', $application->getKey())->where('document_type_id', $documentTypeId)->where('stage', $stage->value)->max('slot') + 1;

        if ($slot > 10) {
            throw new DomainRuleException('Maksimal 10 berkas untuk jenis dokumen ini.', 'TOO_MANY_FILES');
        }

        return ApplicationDocument::query()->create([
            'application_id' => $application->getKey(),
            'document_type_id' => $documentTypeId,
            'stage' => $stage,
            'slot' => $slot,
            'is_mandatory' => false,
            'status' => DocumentStatus::Missing,
        ]);
    }

    /**
     * FR-DOC-01/05: unggah (ulang) membuat versi baru; versi lama tetap.
     */
    public function upload(Application $application, ApplicationDocument $document, UploadedFile $file, Actor $actor): DocumentVersion
    {
        if ($document->application_id !== $application->getKey()) {
            abort(404);
        }

        if (! $actor->isUser()) {
            $this->assertApplicantCanUpload($application, $document, $document->stage);
        }

        if (! in_array($document->status, [DocumentStatus::Missing, DocumentStatus::Uploaded, DocumentStatus::Rejected, DocumentStatus::RevisionRequested], true)) {
            throw new DomainRuleException('Dokumen sudah terkunci.', 'DOCUMENT_LOCKED', 423);
        }

        $type = $document->documentType;
        $upload = $this->storage->store(
            $file,
            (array) $type->allowed_mime_types,
            (int) $type->max_size_kb,
            [$application->period?->code ?? 'periode', (string) $application->getKey(), $type->code],
            $type->code === 'PHOTO' ? self::PHOTO_RULES : [],
            $actor->userId(),
        );

        if ($upload->status === FileUploadStatus::Rejected) {
            throw new DomainRuleException('Berkas ditolak oleh pemindaian keamanan. Unggah berkas lain.', 'FILE_REJECTED', 422);
        }

        return DB::transaction(function () use ($application, $document, $upload, $actor): DocumentVersion {
            $document = ApplicationDocument::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();
            $versionNo = (int) DocumentVersion::query()->where('application_document_id', $document->getKey())->max('version_no') + 1;

            $version = DocumentVersion::query()->create([
                'university_id' => $application->university_id,
                'application_document_id' => $document->getKey(),
                'version_no' => $versionNo,
                'file_upload_id' => $upload->getKey(),
                'uploaded_by_type' => $actor->type,
                'uploaded_by_id' => (string) $actor->id,
                'checksum' => $upload->checksum,
            ]);

            $upload->forceFill(['fileable_type' => $version->getMorphClass(), 'fileable_id' => $version->getKey()])->save();

            $from = $document->status;
            StateTransition::assert($from, DocumentStatus::Uploaded, 'dokumen');
            $document->forceFill(['status' => DocumentStatus::Uploaded, 'current_version_id' => $version->getKey()])->save();

            $this->history->record($application, Lifecycle::Document, $from, DocumentStatus::Uploaded, $actor, metadata: [
                'document_id' => $document->getKey(), 'document_type' => $document->documentType?->code, 'version_no' => $versionNo,
            ], auditSubject: $document);

            return $version;
        });
    }

    /**
     * FR-DOC-04: keputusan per dokumen (versi terbaru saja). Kode alasan wajib
     * selain approved. SUSPECTED_FORGERY → eskalasi ke Admin PMB.
     *
     * @param  array{decision: string, reason_code?: ?string, note_for_applicant?: ?string, internal_note?: ?string, version_id: string}  $data
     */
    public function review(ApplicationDocument $document, array $data, User $reviewer): ApplicationDocument
    {
        $decision = ReviewDecision::from($data['decision']);
        $target = match ($decision) {
            ReviewDecision::Approved => DocumentStatus::Approved,
            ReviewDecision::Rejected => DocumentStatus::Rejected,
            ReviewDecision::RevisionRequested, ReviewDecision::Reopened => DocumentStatus::RevisionRequested,
        };

        if ($decision !== ReviewDecision::Approved && empty($data['reason_code'])) {
            throw ValidationException::withMessages(['reason_code' => 'Kode alasan wajib dipilih.']);
        }

        if (! empty($data['reason_code']) && ! in_array($data['reason_code'], DocumentReasonCodes::DOCUMENT, true)) {
            throw ValidationException::withMessages(['reason_code' => 'Kode alasan tidak dikenal.']);
        }

        return DB::transaction(function () use ($document, $data, $decision, $target, $reviewer): ApplicationDocument {
            $document = ApplicationDocument::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();

            if ($document->current_version_id !== $data['version_id']) {
                throw new ConflictException('Versi dokumen bukan yang terbaru. Muat ulang.', 'VERSION_CONFLICT');
            }

            $version = DocumentVersion::query()->findOrFail($document->current_version_id);

            if ($version->fileUpload?->status !== FileUploadStatus::Clean) {
                throw new ConflictException('Berkas belum lolos pemindaian.', 'FILE_NOT_CLEAN');
            }

            $from = $document->status;
            StateTransition::assert($from, $target, 'dokumen');

            DocumentReview::query()->create([
                'university_id' => $document->university_id,
                'document_version_id' => $version->getKey(),
                'decision' => $decision,
                'reason_code' => $data['reason_code'] ?? null,
                'note_for_applicant' => $data['note_for_applicant'] ?? null,
                'internal_note' => $data['internal_note'] ?? null,
                'reviewed_by' => $reviewer->getKey(),
            ]);

            $document->forceFill([
                'status' => $target,
                'locked_at' => $target === DocumentStatus::Approved ? now() : null,
            ])->save();

            $application = $document->application;
            $this->history->record($application, Lifecycle::Document, $from, $target, Actor::user($reviewer), $data['reason_code'] ?? null, $data['internal_note'] ?? null, [
                'document_id' => $document->getKey(), 'document_type' => $document->documentType?->code,
            ], $document);

            if (($data['reason_code'] ?? null) === 'SUSPECTED_FORGERY') {
                $this->outbox->notifyPermission($application, PmbPermission::VERIFICATION_OVERRIDE, 'pmb.ops.alert', [
                    'message' => 'Dugaan pemalsuan dokumen pada pendaftaran '.($application->registration_number ?? $application->getKey()).'.',
                ]);
            }

            return $document->refresh();
        });
    }

    /** Petugas membuka kembali dokumen approved (SM-03 approved → revision_requested, dengan alasan). */
    public function reopen(ApplicationDocument $document, string $reason, User $actor): ApplicationDocument
    {
        return $this->review($document, [
            'decision' => ReviewDecision::Reopened->value,
            'reason_code' => 'OTHER',
            'internal_note' => $reason,
            'version_id' => (string) $document->current_version_id,
        ], $actor);
    }

    /** FR-DOC-06: kunci seluruh dokumen tahap tertentu (saat verified / daftar ulang approved). */
    public function lockStage(Application $application, DocumentStage $stage): void
    {
        ApplicationDocument::query()->where('application_id', $application->getKey())->where('stage', $stage->value)
            ->whereNull('locked_at')->update(['locked_at' => now()]);
    }

    /**
     * Status ringkas dokumen wajib yang belum terpenuhi untuk syarat submit
     * (≥ uploaded & berkas clean) atau verifikasi (approved).
     *
     * @param  array<string, mixed>  $context  konteks aturan kondisi (nationality, has_achievement, path.category, ...)
     * @return array<int, array{document_id: string, document_type: string, name: string, status: string}>
     */
    public function unmetMandatory(Application $application, DocumentStage $stage, bool $requireApproved, array $context): array
    {
        $requirements = DocumentRequirement::query()->with('documentType')->where('wave_path_id', $application->wave_path_id)->where('stage', $stage->value)->get()
            ->keyBy('document_type_id');
        $documents = ApplicationDocument::query()->with('currentVersion.fileUpload', 'documentType')
            ->where('application_id', $application->getKey())->where('stage', $stage->value)->get()->groupBy('document_type_id');
        $unmet = [];

        foreach ($requirements as $typeId => $requirement) {
            if (! $requirement->is_mandatory || ! \Modules\Admission\Support\FormRuleEvaluator::passes($requirement->condition, $context)) {
                continue;
            }

            $docs = $documents->get($typeId, collect());
            $satisfied = $docs->contains(function (ApplicationDocument $doc) use ($requireApproved): bool {
                if ($requireApproved) {
                    return $doc->status === DocumentStatus::Approved;
                }

                return in_array($doc->status, [DocumentStatus::Uploaded, DocumentStatus::InReview, DocumentStatus::Approved], true)
                    && $doc->currentVersion?->fileUpload?->status === FileUploadStatus::Clean;
            });

            if (! $satisfied) {
                $first = $docs->first();
                $unmet[] = [
                    'document_id' => (string) ($first?->getKey() ?? ''),
                    'document_type' => $requirement->documentType->code,
                    'name' => $requirement->documentType->name,
                    'status' => $first?->status->value ?? DocumentStatus::Missing->value,
                ];
            }
        }

        return $unmet;
    }

    /**
     * URL unduh bertanda tangan untuk staf: dokumen sensitif butuh
     * `pmb_sensitive.read` dan pembukaannya dicatat (FR-DOC-07, AC-18).
     */
    public function staffDownloadUrl(DocumentVersion $version, User $user, string $purpose, ?string $ip): string
    {
        $document = $version->document;
        $sensitive = (bool) $document->documentType?->is_sensitive;

        if ($sensitive && ! $user->hasPermissionTo(PmbPermission::SENSITIVE_READ)) {
            abort(403, 'Dokumen sensitif memerlukan izin akses data sensitif.');
        }

        if ($sensitive) {
            $this->sensitiveLog->log($user, $document->application, 'document:'.$document->documentType?->code, $purpose, $ip);
        }

        $this->history->action($document, \Modules\AuditLog\Enums\AuditAction::Downloaded, ['version_no' => $version->version_no, 'purpose' => $purpose]);

        return $this->storage->temporaryUrl($version->fileUpload);
    }

    public function applicantDownloadUrl(ApplicationDocument $document): string
    {
        $version = $document->currentVersion ?? throw new DomainRuleException('Dokumen belum diunggah.', 'DOCUMENT_MISSING', 404);

        return $this->storage->temporaryUrl($version->fileUpload);
    }

    /**
     * Pendaftar hanya dapat mengunggah saat: tahap pendaftaran → formulir
     * draft, atau `revision_requested` & dokumen termasuk target perbaikan;
     * tahap daftar ulang → daftar ulang aktif (bukan submitted/approved),
     * atau revisi yang menargetkan dokumen tersebut.
     */
    private function assertApplicantCanUpload(Application $application, ?ApplicationDocument $document, DocumentStage $stage): void
    {
        if ($stage === DocumentStage::Application) {
            if ($application->form_status === FormStatus::Draft) {
                return;
            }

            $targets = (array) ($application->revision_targets['documents'] ?? []);

            if ($application->form_status === FormStatus::RevisionRequested
                && ($document === null || in_array($document->getKey(), $targets, true) || in_array($document->documentType?->code, $targets, true))
                && ($application->revision_due_at === null || $application->revision_due_at->isFuture())) {
                return;
            }

            throw new DomainRuleException('Dokumen terkunci. Perubahan hanya dapat dilakukan bila panitia meminta perbaikan.', 'DOCUMENT_LOCKED', 423);
        }

        $reRegistration = $application->reRegistration;

        $open = $reRegistration !== null && in_array($reRegistration->status, [ReRegistrationStatus::Confirmed, ReRegistrationStatus::InProgress, ReRegistrationStatus::RevisionRequested], true)
            && $reRegistration->effectiveDeadline()->isFuture();

        if ($reRegistration?->status === ReRegistrationStatus::RevisionRequested && $document !== null) {
            $targets = (array) ($reRegistration->revision_targets['documents'] ?? []);
            $open = $open && (in_array($document->getKey(), $targets, true) || in_array($document->documentType?->code, $targets, true));
        }

        if (! $open) {
            throw new DomainRuleException('Unggah dokumen daftar ulang tidak tersedia pada status saat ini.', 'DOCUMENT_LOCKED', 423);
        }
    }
}
