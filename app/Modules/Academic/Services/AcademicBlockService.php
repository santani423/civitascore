<?php

namespace Modules\Academic\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Enums\AcademicBlockStatus;
use Modules\Academic\Enums\BlockExemptionStatus;
use Modules\Academic\Models\AcademicBlock;
use Modules\Academic\Models\AcademicBlockExemption;
use Modules\Academic\Models\AcademicBlockHistory;
use Modules\Academic\Models\Student;

/**
 * Mesin cekal SIAKAD (docs/academic-learning/01-siakad.md A.8) — tahap
 * pertama tanpa tabel aturan generik: sumber (Modul KEU) memutuskan kapan
 * cekal terbit/dicabut, service ini menyimpan, mencatat riwayat, mengelola
 * dispensasi, dan menegakkan larangan di titik layanan:
 *
 *   allowed(student, service) = tidak ada cekal aktif yang membatasi
 *   `service` tanpa dispensasi aktif & berlaku untuk `service` (A.8.4).
 */
class AcademicBlockService
{
    public const SERVICES = [
        'krs' => 'Pengisian KRS',
        'exam' => 'Ujian',
        'view_grades' => 'Melihat nilai',
        'transcript' => 'Transkrip',
        'yudisium' => 'Yudisium',
        'graduation' => 'Wisuda',
    ];

    /**
     * Layanan yang SUDAH ditegakkan di server (KrsPlanService, StudentExamController).
     * Layanan lain di SERVICES dicadangkan untuk tahap berikutnya — aturan
     * cekal hanya boleh memakai layanan di sini agar tidak menjanjikan
     * pemblokiran yang belum ada.
     */
    public const ENFORCED_SERVICES = ['krs', 'exam'];

    /**
     * Terbitkan cekal idempoten per (mahasiswa, sumber, kode aturan). Bila
     * sudah aktif, alasan & rincian diperbarui tanpa riwayat baru.
     *
     * @param  array<int, string>  $services
     * @param  array<string, mixed>  $details
     */
    public function impose(Student $student, string $source, string $ruleCode, string $blockType, array $services, string $reason, array $details = [], ?string $termId = null, ?User $actor = null): AcademicBlock
    {
        return DB::transaction(function () use ($student, $source, $ruleCode, $blockType, $services, $reason, $details, $termId, $actor): AcademicBlock {
            $key = $this->activeKey($student, $source, $ruleCode);
            $existing = AcademicBlock::query()->where('active_key', $key)->lockForUpdate()->first();

            if ($existing !== null) {
                $existing->forceFill(['reason' => $reason, 'details' => $details, 'restricted_services' => array_values($services)])->save();

                return $existing;
            }

            $block = AcademicBlock::query()->create([
                'university_id' => $student->university_id,
                'student_id' => $student->id,
                'academic_term_id' => $termId,
                'block_type' => $blockType,
                'source' => $source,
                'source_rule_code' => $ruleCode,
                'restricted_services' => array_values($services),
                'reason' => $reason,
                'details' => $details,
                'status' => AcademicBlockStatus::Active,
                'active_key' => $key,
                'effective_from' => now(),
                'imposed_by' => $actor?->id,
            ]);

            $this->history($block, 'imposed', null, AcademicBlockStatus::Active, $actor, $reason, $details);

            return $block;
        });
    }

    public function activeBlockFor(Student $student, string $source, string $ruleCode): ?AcademicBlock
    {
        return AcademicBlock::query()->where('active_key', $this->activeKey($student, $source, $ruleCode))->first();
    }

    public function revoke(AcademicBlock $block, string $reason, ?User $actor = null): AcademicBlock
    {
        return DB::transaction(function () use ($block, $reason, $actor): AcademicBlock {
            $block = AcademicBlock::query()->lockForUpdate()->findOrFail($block->id);

            if ($block->status === AcademicBlockStatus::Revoked) {
                return $block;
            }

            $from = $block->status;
            $block->forceFill([
                'status' => AcademicBlockStatus::Revoked,
                'active_key' => null,
                'revoked_by' => $actor?->id,
                'revoked_at' => now(),
                'revoke_reason' => $reason,
            ])->save();

            $block->exemptions()->whereIn('status', [BlockExemptionStatus::Pending, BlockExemptionStatus::Active])
                ->update(['status' => BlockExemptionStatus::Revoked->value, 'decision_note' => 'Cekal dicabut.']);

            $this->history($block, 'revoked', $from, AcademicBlockStatus::Revoked, $actor, $reason);

            return $block;
        });
    }

    /**
     * Dispensasi per layanan. `$autoApprove` = pemberi berwenang langsung
     * memberi (status aktif); selain itu tercatat menunggu persetujuan.
     */
    public function requestExemption(AcademicBlock $block, string $service, CarbonImmutable $validUntil, string $reason, ?User $requester, bool $autoApprove, ?string $promiseAmount = null, ?string $promiseDate = null): AcademicBlockExemption
    {
        if ($block->status === AcademicBlockStatus::Revoked) {
            throw new ConflictException('Cekal ini sudah dicabut sehingga tidak memerlukan dispensasi.');
        }

        if (! in_array($service, $block->restricted_services ?? [], true)) {
            throw new ConflictException('Layanan tersebut tidak dibatasi oleh cekal ini.');
        }

        if ($validUntil->lessThanOrEqualTo(now())) {
            throw new ConflictException('Batas akhir dispensasi harus setelah saat ini.');
        }

        return DB::transaction(function () use ($block, $service, $validUntil, $reason, $requester, $autoApprove, $promiseAmount, $promiseDate): AcademicBlockExemption {
            $exemption = AcademicBlockExemption::query()->create([
                'university_id' => $block->university_id,
                'academic_block_id' => $block->id,
                'service' => $service,
                'valid_from' => now(),
                'valid_until' => $validUntil,
                'reason' => $reason,
                'promise_amount' => $promiseAmount,
                'promise_date' => $promiseDate,
                'status' => $autoApprove ? BlockExemptionStatus::Active : BlockExemptionStatus::Pending,
                'requested_by' => $requester?->id,
                'granted_by' => $autoApprove ? $requester?->id : null,
                'decided_at' => $autoApprove ? now() : null,
            ]);

            $this->history($block, $autoApprove ? 'exempted' : 'exemption_requested', $block->status, $block->status, $requester, $reason, ['service' => $service, 'valid_until' => $validUntil->toIso8601String()]);
            $this->syncStatus($block, $requester);

            return $exemption;
        });
    }

    public function decideExemption(AcademicBlockExemption $exemption, bool $approve, User $actor, ?string $note = null): AcademicBlockExemption
    {
        return DB::transaction(function () use ($exemption, $approve, $actor, $note): AcademicBlockExemption {
            $exemption = AcademicBlockExemption::query()->lockForUpdate()->findOrFail($exemption->id);

            if ($exemption->status !== BlockExemptionStatus::Pending) {
                throw new ConflictException('Dispensasi ini sudah diputuskan.');
            }

            if ($exemption->requested_by !== null && $exemption->requested_by === $actor->id) {
                throw new ConflictException('Pengaju dispensasi tidak dapat menyetujui pengajuannya sendiri (pemisahan tugas).', 'SELF_APPROVAL_FORBIDDEN');
            }

            $exemption->forceFill([
                'status' => $approve ? BlockExemptionStatus::Active : BlockExemptionStatus::Rejected,
                'granted_by' => $approve ? $actor->id : null,
                'decided_at' => now(),
                'decision_note' => $note,
            ])->save();

            $block = $exemption->block;
            $this->history($block, $approve ? 'exempted' : 'exemption_rejected', $block->status, $block->status, $actor, $note, ['service' => $exemption->service]);
            $this->syncStatus($block, $actor);

            return $exemption;
        });
    }

    public function revokeExemption(AcademicBlockExemption $exemption, string $reason, User $actor): AcademicBlockExemption
    {
        return DB::transaction(function () use ($exemption, $reason, $actor): AcademicBlockExemption {
            $exemption->forceFill(['status' => BlockExemptionStatus::Revoked, 'decision_note' => $reason])->save();
            $this->history($exemption->block, 'exemption_revoked', $exemption->block->status, $exemption->block->status, $actor, $reason, ['service' => $exemption->service]);
            $this->syncStatus($exemption->block, $actor);

            return $exemption;
        });
    }

    /** Dispensasi lewat masa berlaku → expired; cekal kembali aktif bila perlu (BR-SIA-CKL-06). */
    public function expireExemptions(): int
    {
        $expired = AcademicBlockExemption::query()
            ->where('status', BlockExemptionStatus::Active)
            ->where('valid_until', '<=', now())
            ->with('block')
            ->get();

        foreach ($expired as $exemption) {
            $exemption->forceFill(['status' => BlockExemptionStatus::Expired])->save();
            $this->history($exemption->block, 'exemption_expired', $exemption->block->status, $exemption->block->status, null, null, ['service' => $exemption->service]);
            $this->syncStatus($exemption->block, null);
        }

        return $expired->count();
    }

    /**
     * Cekal yang saat ini membatasi `service` bagi mahasiswa (tanpa dispensasi berlaku).
     *
     * @return Collection<int, AcademicBlock>
     */
    public function blocking(Student $student, string $service): Collection
    {
        $now = now();

        return AcademicBlock::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [AcademicBlockStatus::Active, AcademicBlockStatus::Exempted])
            ->with(['exemptions' => fn ($query) => $query->where('status', BlockExemptionStatus::Active)])
            ->get()
            ->filter(fn (AcademicBlock $block) => in_array($service, $block->restricted_services ?? [], true)
                && ! $block->exemptions->contains(fn (AcademicBlockExemption $e) => $e->service === $service && $e->valid_from <= $now && $e->valid_until > $now))
            ->values();
    }

    /**
     * Pesan larangan untuk pengguna (tanpa nominal — BR-SIA-CKL-09).
     *
     * @return array<int, array{block_id: string, block_type: string, source: string, reason: string, since: string}>
     */
    public function blockers(Student $student, string $service): array
    {
        return $this->blocking($student, $service)->map(fn (AcademicBlock $block) => [
            'block_id' => $block->id,
            'block_type' => $block->block_type,
            'source' => $block->source,
            'reason' => $block->reason,
            'since' => $block->effective_from->toIso8601String(),
        ])->all();
    }

    public function assertAllowed(Student $student, string $service): void
    {
        $blockers = $this->blockers($student, $service);

        if ($blockers === []) {
            return;
        }

        $label = self::SERVICES[$service] ?? $service;
        $reasons = implode(' ', array_unique(array_map(fn (array $b) => $b['reason'], $blockers)));

        throw new ConflictException("{$label} tidak dapat dilakukan karena terdapat cekal: {$reasons}", 'ACADEMIC_BLOCKED', ['blocks' => $blockers]);
    }

    /** Status `exempted` bila seluruh layanan terbatas punya dispensasi aktif, selain itu `active`. */
    private function syncStatus(AcademicBlock $block, ?User $actor): void
    {
        $block->refresh();

        if ($block->status === AcademicBlockStatus::Revoked) {
            return;
        }

        $now = now();
        $exempted = $block->exemptions()->where('status', BlockExemptionStatus::Active)->where('valid_until', '>', $now)->pluck('service')->unique()->all();
        $allCovered = array_diff($block->restricted_services ?? [], $exempted) === [];
        $target = $allCovered ? AcademicBlockStatus::Exempted : AcademicBlockStatus::Active;

        if ($block->status !== $target) {
            $from = $block->status;
            $block->forceFill(['status' => $target])->save();
            $this->history($block, $target === AcademicBlockStatus::Exempted ? 'fully_exempted' : 'reactivated', $from, $target, $actor);
        }
    }

    /** @param  array<string, mixed>  $payload */
    private function history(AcademicBlock $block, string $action, ?AcademicBlockStatus $from, ?AcademicBlockStatus $to, ?User $actor, ?string $reason = null, array $payload = []): void
    {
        AcademicBlockHistory::query()->create([
            'university_id' => $block->university_id,
            'academic_block_id' => $block->id,
            'action' => $action,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'actor_id' => $actor?->id,
            'reason' => $reason,
            'payload' => $payload === [] ? null : $payload,
        ]);
    }

    private function activeKey(Student $student, string $source, string $ruleCode): string
    {
        return "{$student->id}:{$source}:{$ruleCode}";
    }
}
