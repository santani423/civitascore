<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\RefundStatus;
use Modules\Finance\Models\Refund;
use Modules\Finance\Support\Approvals;
use Modules\Finance\Support\FinanceNumbering;
use Modules\Finance\Support\Money;

/**
 * Pengembalian dana (FR-RFD-05). Selalu dari saldo deposit (KD-KEU-08):
 * kelebihan/pembayaran ganda/pembatalan tagihan sudah lebih dulu menjadi
 * deposit. Dana ditahan saat diajukan, dilepas bila ditolak/gagal
 * (BR-KEU-58..60). Pengaju ≠ penyetuju (SOD-KEU-04).
 */
class RefundService
{
    public const REASONS = [
        'OVERPAYMENT' => 'Kelebihan bayar',
        'DUPLICATE_PAYMENT' => 'Pembayaran ganda',
        'CANCELLED_INVOICE' => 'Tagihan dibatalkan/dikoreksi',
        'WITHDRAWAL' => 'Mengundurkan diri',
        'LEAVE' => 'Cuti akademik',
        'DECEASED' => 'Mahasiswa meninggal dunia (ke ahli waris)',
        'GRADUATED_BALANCE' => 'Sisa saldo setelah lulus/keluar',
        'OTHER' => 'Lainnya',
    ];

    public function __construct(
        private readonly DepositService $deposits,
        private readonly FinanceNumbering $numbering,
        private readonly FinanceNotifier $notifier,
        private readonly AuditLogService $audit,
    ) {}

    /** @param  array{bank: string, account: string, name: string, relation: string}  $beneficiary */
    public function request(Student $student, string $amount, string $reasonCode, string $reasonNote, array $beneficiary, User $actor): Refund
    {
        $cents = Money::toCents($amount);

        if ($cents <= 0) {
            throw new ConflictException('Nominal pengembalian harus lebih dari nol.');
        }

        if ($beneficiary['relation'] !== 'self' && $beneficiary['relation'] !== 'parent' && empty($beneficiary['power_of_attorney'])) {
            throw new ConflictException('Rekening atas nama pihak lain memerlukan surat kuasa (BR-KEU-59).');
        }

        $refund = DB::transaction(function () use ($student, $cents, $reasonCode, $reasonNote, $beneficiary, $actor): Refund {
            $refund = Refund::query()->create([
                'refund_number' => $this->numbering->next('refund'),
                'student_id' => $student->id,
                'source_type' => 'deposit',
                'amount' => Money::fromCents($cents),
                'reason_code' => $reasonCode,
                'reason_note' => $reasonNote,
                'beneficiary_bank' => $beneficiary['bank'],
                'beneficiary_account' => $beneficiary['account'],
                'beneficiary_name' => $beneficiary['name'],
                'beneficiary_relation' => $beneficiary['relation'],
                'status' => RefundStatus::PendingApproval,
                'requested_by' => $actor->id,
            ]);

            $this->deposits->hold($student, $cents, 'refund', $refund->id, $actor->id);

            return $refund;
        });

        $this->audit->recordAction($refund, AuditAction::Created, ['amount' => (string) $refund->amount, 'reason' => $reasonCode], $reasonNote);

        return $refund;
    }

    public function approve(Refund $refund, User $actor, ?string $note): Refund
    {
        $this->assertStatus($refund, RefundStatus::PendingApproval);
        Approvals::assertDistinct($actor, [$refund->requested_by], 'Pengaju pengembalian dana tidak dapat menyetujuinya sendiri (pemisahan tugas).');
        $refund->forceFill(['status' => RefundStatus::Approved, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => $note])->save();
        $this->audit->recordAction($refund, AuditAction::Approved, [], $note);
        $this->notify($refund, 'disetujui, menunggu pelaksanaan transfer');

        return $refund;
    }

    public function reject(Refund $refund, User $actor, string $note): Refund
    {
        $this->assertStatus($refund, RefundStatus::PendingApproval);
        Approvals::assertDistinct($actor, [$refund->requested_by]);

        DB::transaction(function () use ($refund, $actor, $note): void {
            $this->deposits->release($refund->student, Money::toCents($refund->amount), 'refund', $refund->id, $actor->id);
            $refund->forceFill(['status' => RefundStatus::Rejected, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => $note])->save();
        });

        $this->audit->recordAction($refund, AuditAction::Rejected, [], $note);
        $this->notify($refund, 'ditolak: '.$note);

        return $refund;
    }

    public function cancel(Refund $refund, User $actor): Refund
    {
        $this->assertStatus($refund, RefundStatus::PendingApproval);

        DB::transaction(function () use ($refund, $actor): void {
            $this->deposits->release($refund->student, Money::toCents($refund->amount), 'refund', $refund->id, $actor->id);
            $refund->forceFill(['status' => RefundStatus::Cancelled])->save();
        });

        return $refund;
    }

    /** Transfer dilaksanakan di luar sistem; bukti/referensi wajib (BR-KEU-60). */
    public function complete(Refund $refund, string $reference, ?string $proofFileId, User $actor): Refund
    {
        $this->assertStatus($refund, RefundStatus::Approved);

        DB::transaction(function () use ($refund, $reference, $proofFileId, $actor): void {
            $this->deposits->payout($refund->student, Money::toCents($refund->amount), 'refund', $refund->id, $actor->id);
            $refund->forceFill(['status' => RefundStatus::Completed, 'executed_by' => $actor->id, 'executed_at' => now(), 'execution_reference' => $reference, 'proof_file_id' => $proofFileId])->save();
        });

        $this->audit->recordAction($refund, AuditAction::Updated, ['status' => 'completed', 'reference' => $reference]);
        $this->notify($refund, 'selesai ditransfer');

        return $refund;
    }

    public function fail(Refund $refund, string $reason, User $actor): Refund
    {
        $this->assertStatus($refund, RefundStatus::Approved);

        DB::transaction(function () use ($refund, $reason, $actor): void {
            $this->deposits->release($refund->student, Money::toCents($refund->amount), 'refund', $refund->id, $actor->id);
            $refund->forceFill(['status' => RefundStatus::Failed, 'failure_reason' => $reason, 'executed_by' => $actor->id, 'executed_at' => now()])->save();
        });

        $this->notify($refund, 'gagal: '.$reason.'. Dana dikembalikan ke saldo deposit.');

        return $refund;
    }

    private function assertStatus(Refund $refund, RefundStatus $expected): void
    {
        if ($refund->status !== $expected) {
            throw new ConflictException('Status pengembalian dana saat ini: '.$refund->status->label().'.');
        }
    }

    private function notify(Refund $refund, string $status): void
    {
        $this->notifier->student($refund->student, 'keu.refund_status', ['number' => $refund->refund_number, 'amount' => Money::format($refund->amount), 'status' => $status]);
    }
}
