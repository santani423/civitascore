<?php

namespace Modules\Admission\Services\Announcement;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\AnnouncementBatchStatus;
use Modules\Admission\Enums\ChangeRequestStatus;
use Modules\Admission\Enums\ChangeRequestType;
use Modules\Admission\Enums\Decision;
use Modules\Admission\Enums\DecisionSource;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\PublicationStatus;
use Modules\Admission\Enums\ReRegistrationStatus;
use Modules\Admission\Enums\RunStatus;
use Modules\Admission\Enums\WaveStatus;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\AnnouncementBatch;
use Modules\Admission\Models\ChangeRequest;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\ReRegistration;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Approval\PmbApprovalService;
use Modules\Admission\Services\Selection\SelectionRunService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\PmbPermission;
use Modules\AuditLog\Enums\AuditAction;
use Throwable;

/**
 * Publikasi hasil terjadwal (FR-ANN-03, J.3, SM-08/09): HANYA oleh job/
 * pemicu sistem untuk run `approved`; hash diverifikasi ulang; kuota
 * diperbarui di bawah lock; daftar ulang dibuat untuk yang diterima; LoA &
 * notifikasi diantrekan SETELAH commit. Tidak ada tombol "publikasi sekarang".
 */
class PublicationService
{
    public function __construct(
        private readonly SelectionRunService $runs,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly PmbApprovalService $approvals,
    ) {}

    /**
     * Dipanggil scheduler tiap menit DAN secara malas (lazy) dari endpoint
     * hasil/cek status — agar publikasi tetap tepat waktu walau scheduler
     * server (shared hosting) tidak berjalan. Dilindungi lock cache.
     */
    public function publishDue(): int
    {
        $lock = Cache::lock('pmb:publish-due', 120);

        if (! $lock->get()) {
            return 0;
        }

        try {
            $count = 0;

            foreach (AnnouncementBatch::query()->withoutGlobalScopes()->where('status', AnnouncementBatchStatus::Scheduled->value)->where('scheduled_publish_at', '<=', now())->get() as $batch) {
                $tenant = app(\App\Support\Tenancy\TenantContext::class);
                $previous = $tenant->universityId();
                $tenant->setUniversityId((string) $batch->university_id);

                try {
                    $count += $this->publish($batch);
                } finally {
                    $tenant->setUniversityId($previous);
                }
            }

            return $count;
        } finally {
            $lock->release();
        }
    }

    /** @return int jumlah keputusan terbit */
    public function publish(AnnouncementBatch $batch): int
    {
        $claimed = AnnouncementBatch::query()->whereKey($batch->getKey())->where('status', AnnouncementBatchStatus::Scheduled->value)
            ->update(['status' => AnnouncementBatchStatus::Publishing->value]);

        if ($claimed === 0) {
            return 0;
        }

        $run = SelectionRun::query()->with('wavePath.wave')->findOrFail($batch->selection_run_id);

        if ($run->status !== RunStatus::Approved || $run->result_hash !== $this->runs->currentHash($run)) {
            $reason = $run->status !== RunStatus::Approved ? 'Run tidak berstatus approved.' : 'result_hash tidak cocok — publikasi dibatalkan.';
            $batch->forceFill(['status' => AnnouncementBatchStatus::Failed, 'failure_reason' => $reason])->save();
            $this->outbox->notifyPermission($run, PmbPermission::CONFIG_MANAGE, 'pmb.ops.alert', ['message' => 'Publikasi hasil gagal: '.$reason]);
            Log::error('[pmb-publish] '.$reason, ['batch' => $batch->getKey()]);

            return 0;
        }

        try {
            $count = DB::transaction(function () use ($batch, $run): int {
                $decisions = AdmissionDecision::query()->with('application.account')->where('selection_run_id', $run->getKey())
                    ->where('publication_status', PublicationStatus::Unpublished->value)->where('is_current', true)->get();
                $isCorrection = $run->run_type === 'correction';

                foreach ($decisions as $decision) {
                    $this->publishDecision($decision, $batch, $isCorrection);
                }

                $batch->forceFill(['status' => AnnouncementBatchStatus::Published, 'published_at' => now(), 'decisions_count' => $decisions->count()])->save();
                $run->forceFill(['status' => RunStatus::Published])->save();

                $wave = $run->wavePath->wave;

                if (in_array($wave->status, [WaveStatus::Closed, WaveStatus::Open], true)) {
                    $wave->forceFill(['status' => WaveStatus::Announced])->save();
                }

                $this->history->action($run, AuditAction::Updated, ['status' => 'published', 'decisions' => $decisions->count()]);

                return $decisions->count();
            });

            return $count;
        } catch (Throwable $e) {
            AnnouncementBatch::query()->whereKey($batch->getKey())->update(['status' => AnnouncementBatchStatus::Failed->value, 'failure_reason' => mb_substr($e->getMessage(), 0, 2000)]);
            $this->outbox->notifyPermission($run, PmbPermission::CONFIG_MANAGE, 'pmb.ops.alert', ['message' => 'Publikasi hasil gagal: '.$e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Terbitkan satu keputusan: versi sebelumnya superseded; kuota accepted
     * dikunci & divalidasi (BR-05, KD-11); daftar ulang dibuat/diperbarui;
     * LoA & notifikasi lewat outbox (terkirim setelah commit, J.3 butir 3).
     */
    public function publishDecision(AdmissionDecision $decision, ?AnnouncementBatch $batch, bool $isCorrection): void
    {
        $previous = $decision->previous_decision_id ? AdmissionDecision::query()->find($decision->previous_decision_id) : null;
        $previousAccepted = $previous !== null && $previous->decision === Decision::Accepted && $previous->publication_status === PublicationStatus::Published;
        $reRegistration = ReRegistration::query()->where('application_id', $decision->application_id)->lockForUpdate()->first();
        $seatHeld = $previousAccepted && $reRegistration !== null && ! in_array($reRegistration->status, [ReRegistrationStatus::Declined, ReRegistrationStatus::Expired, ReRegistrationStatus::Cancelled], true);

        if ($previous !== null && $previous->publication_status !== PublicationStatus::Superseded) {
            $previous->forceFill(['publication_status' => PublicationStatus::Superseded, 'is_current' => false])->save();
        }

        if ($decision->decision === Decision::Accepted) {
            $sameSeat = $seatHeld && $previous->quota_id === $decision->quota_id;

            if (! $sameSeat) {
                $this->takeSeat((string) $decision->quota_id);

                if ($seatHeld) {
                    $this->releaseSeat((string) $previous->quota_id);
                }
            }
        } elseif ($seatHeld) {
            $this->releaseSeat((string) $previous->quota_id);
        }

        $decision->forceFill(['publication_status' => PublicationStatus::Published, 'published_at' => now(), 'announcement_batch_id' => $batch?->getKey()])->save();

        $application = $decision->application;
        $this->history->record($application, Lifecycle::Decision, $previous?->decision?->value, $decision->decision, Actor::system(), $isCorrection ? 'CORRECTION' : null, $decision->correction_note, [
            'decision_id' => $decision->getKey(), 'version' => $decision->decision_version, 'source' => $decision->source->value,
        ], $decision);

        if ($decision->decision === Decision::Accepted) {
            $deadline = $decision->source === DecisionSource::WaitlistPromotion
                ? null
                : CarbonImmutable::parse($application->wave->re_registration_closes_at);
            $this->ensureReRegistration($decision, $reRegistration, $deadline);
            $this->outbox->record(OutboxRecorder::DECISION_PUBLISHED, $decision, ['decision_id' => $decision->getKey()]);
        } elseif ($reRegistration !== null && $reRegistration->status->isActive()) {
            $from = $reRegistration->status;
            $reRegistration->forceFill(['status' => ReRegistrationStatus::Cancelled, 'cancelled_at' => now()])->save();
            $this->history->record($application, Lifecycle::ReRegistration, $from, ReRegistrationStatus::Cancelled, Actor::system(), 'DECISION_CORRECTED');
            $this->revokeLetter($previous, 'Keputusan dikoreksi');
        }

        $event = $isCorrection || $decision->source === DecisionSource::Correction ? 'pmb.result.corrected' : 'pmb.result.published';
        $this->outbox->notifyApplicant($application->account, $event, [], aggregate: $decision);
    }

    public function ensureReRegistration(AdmissionDecision $decision, ?ReRegistration $existing, ?CarbonImmutable $deadline): ReRegistration
    {
        $deadline ??= now()->addDays(max(1, (int) app(\Modules\Admission\Support\PmbSettings::class)->int('pmb.waitlist_deadline_days')))->endOfDay();

        if ($existing !== null && $existing->status->isActive()) {
            $existing->forceFill(['decision_id' => $decision->getKey()])->save();

            return $existing;
        }

        if ($existing !== null) {
            // Versi keputusan baru setelah terminal (mis. dipulihkan): pakai baris yang sama (unique per aplikasi).
            $existing->forceFill([
                'decision_id' => $decision->getKey(),
                'status' => ReRegistrationStatus::AwaitingConfirmation,
                'deadline_at' => $deadline,
                'extended_until' => null,
                'declined_at' => null,
                'expired_at' => null,
                'cancelled_at' => null,
            ])->save();

            return $existing;
        }

        $reRegistration = ReRegistration::query()->create([
            'university_id' => $decision->university_id,
            'application_id' => $decision->application_id,
            'decision_id' => $decision->getKey(),
            'status' => ReRegistrationStatus::AwaitingConfirmation,
            'deadline_at' => $deadline,
        ]);

        $this->history->record($decision->application, Lifecycle::ReRegistration, null, ReRegistrationStatus::AwaitingConfirmation, Actor::system());

        return $reRegistration;
    }

    /** Kursi diterima: lock baris kuota, tolak bila melebihi kapasitas (EC-08). */
    public function takeSeat(string $quotaId): void
    {
        $quota = Quota::query()->whereKey($quotaId)->lockForUpdate()->firstOrFail();

        if ($quota->accepted_count >= $quota->capacity) {
            throw new ConflictException('Kuota prodi sudah penuh.', 'QUOTA_FULL');
        }

        $quota->forceFill(['accepted_count' => $quota->accepted_count + 1, 'lock_version' => $quota->lock_version + 1])->save();
    }

    public function releaseSeat(string $quotaId): void
    {
        $quota = Quota::query()->whereKey($quotaId)->lockForUpdate()->first();

        if ($quota !== null) {
            $quota->forceFill(['accepted_count' => max(0, $quota->accepted_count - 1), 'lock_version' => $quota->lock_version + 1])->save();
        }
    }

    public function revokeLetter(?AdmissionDecision $decision, string $reason): void
    {
        $letter = $decision?->letter;

        if ($letter !== null && $letter->status->value !== 'revoked') {
            $letter->forceFill(['status' => 'revoked', 'revoked_at' => now(), 'revoke_reason' => $reason])->save();
        }
    }

    /** Undur jadwal publikasi (≥ semula, alasan wajib) — 11 §A.12 PATCH. */
    public function reschedule(AnnouncementBatch $batch, string $publishAt, string $reason, User $actor): AnnouncementBatch|ChangeRequest
    {
        if ($batch->status !== AnnouncementBatchStatus::Scheduled) {
            throw new ConflictException('Batch sudah diproses.', 'INVALID_TRANSITION');
        }

        $target = CarbonImmutable::parse($publishAt);

        if ($target->lt($batch->scheduled_publish_at)) {
            // Percepatan = perubahan yang memerlukan approval ulang (J.3 butir 6).
            return $this->requestAcceleration($batch, $target, $reason, $actor);
        }

        $old = $batch->scheduled_publish_at->toIso8601String();
        $batch->forceFill(['scheduled_publish_at' => $target])->save();
        $this->history->change($batch, ['scheduled_publish_at' => $old], ['scheduled_publish_at' => $target->toIso8601String()], $reason);

        return $batch;
    }

    public function cancel(AnnouncementBatch $batch, string $reason, User $actor): AnnouncementBatch
    {
        if ($batch->status !== AnnouncementBatchStatus::Scheduled) {
            throw new ConflictException('Batch sudah diterbitkan atau diproses.', 'INVALID_TRANSITION');
        }

        $batch->forceFill(['status' => AnnouncementBatchStatus::Cancelled, 'failure_reason' => $reason])->save();
        $this->history->action($batch, AuditAction::Updated, ['status' => 'cancelled'], $reason);

        return $batch;
    }

    /** Batch baru untuk run approved yang batch-nya dibatalkan/gagal (≥ waktu pengumuman gelombang). */
    public function scheduleNew(SelectionRun $run, string $publishAt, User $actor): AnnouncementBatch
    {
        if ($run->status !== RunStatus::Approved) {
            throw new ConflictException('Run belum disahkan.', 'INVALID_TRANSITION');
        }

        if ($run->batches()->whereIn('status', ['scheduled', 'publishing'])->exists()) {
            throw new ConflictException('Run sudah memiliki batch terjadwal.', 'DUPLICATE');
        }

        $target = CarbonImmutable::parse($publishAt);
        $earliest = CarbonImmutable::parse($run->wavePath->wave->announcement_at);

        if ($target->lt($earliest) && ! $run->wavePath->wave->is_ods) {
            throw ValidationException::withMessages(['scheduled_publish_at' => 'Jadwal tidak boleh sebelum waktu pengumuman gelombang (percepatan butuh persetujuan).']);
        }

        return AnnouncementBatch::query()->create(['selection_run_id' => $run->getKey(), 'scheduled_publish_at' => $target, 'status' => 'scheduled']);
    }

    private function requestAcceleration(AnnouncementBatch $batch, CarbonImmutable $target, string $reason, User $actor): ChangeRequest
    {
        $change = ChangeRequest::query()->create([
            'type' => ChangeRequestType::PublicationAcceleration,
            'subject_type' => 'AnnouncementBatch',
            'subject_id' => $batch->getKey(),
            'payload' => ['scheduled_publish_at' => $target->toIso8601String()],
            'reason' => $reason,
            'status' => ChangeRequestStatus::PendingApproval,
            'requested_by' => $actor->getKey(),
        ]);
        $approval = $this->approvals->submit($change, PmbApprovalService::PUBLICATION_ACCELERATION, $actor, $reason);
        $change->forceFill(['approval_request_id' => $approval->getKey()])->save();

        return $change;
    }

    public function applyAcceleration(ChangeRequest $change): void
    {
        $batch = AnnouncementBatch::query()->find($change->subject_id);

        if ($batch !== null && $batch->status === AnnouncementBatchStatus::Scheduled) {
            $batch->forceFill(['scheduled_publish_at' => CarbonImmutable::parse($change->payload['scheduled_publish_at'])])->save();
        }

        $change->forceFill(['status' => ChangeRequestStatus::Applied, 'applied_at' => now()])->save();
    }
}
