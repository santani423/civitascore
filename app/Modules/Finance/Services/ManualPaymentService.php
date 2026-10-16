<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\FileManagement\Models\FileUpload;
use Modules\FileManagement\Support\FileAttacher;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\ProofStatus;
use Modules\Finance\Enums\ReconciliationStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\PaymentProof;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Support\Approvals;
use Modules\Finance\Support\FinanceNumbering;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;

/**
 * Transfer manual (F-10): siapkan instruksi (nominal + kode unik opsional)
 * → unggah bukti → verifikasi petugas → posting. Bukti dengan checksum sama
 * ditandai duplikat (BR-KEU-50). Persetujuan memakai tanggal transfer pada
 * bukti sebagai tanggal bayar efektif (BR-KEU-37).
 */
class ManualPaymentService
{
    public const REJECT_REASONS = [
        'NOT_FOUND_IN_STATEMENT' => 'Tidak ditemukan di mutasi rekening',
        'AMOUNT_MISMATCH' => 'Nominal tidak sesuai',
        'UNREADABLE' => 'Bukti tidak terbaca',
        'DUPLICATE_PROOF' => 'Bukti ganda',
        'WRONG_ACCOUNT' => 'Rekening tujuan salah',
        'OTHER' => 'Lainnya',
    ];

    public function __construct(
        private readonly PaymentPostingService $posting,
        private readonly TargetResolver $targets,
        private readonly FinanceNumbering $numbering,
        private readonly KeuSettings $settings,
        private readonly FinanceNotifier $notifier,
        private readonly AuditLogService $audit,
    ) {}

    /** Instruksi transfer: transaksi `pending` dengan nominal tepat (+ kode unik). */
    public function prepare(Student $student, string $targetType, ?string $targetId, ?string $amount, User $actor): PaymentTransaction
    {
        [$type, $id, $outstanding] = $this->targets->resolve($student, $targetType, $targetId);
        $cents = $amount !== null && $amount !== '' ? Money::toCents($amount) : $outstanding;

        if ($cents <= 0) {
            throw new ConflictException('Nominal transfer harus lebih dari nol.');
        }

        $uniqueCode = $this->settings->bool('keu.manual_transfer.unique_code') ? random_int(1, 999) : null;

        // Satu instruksi transfer aktif per target: instruksi lama yang belum diberi bukti dibatalkan.
        PaymentTransaction::query()->where('student_id', $student->id)->where('target_id', $id)
            ->where('method', PaymentMethod::ManualTransfer)->where('status', TransactionStatus::Pending)
            ->update(['status' => TransactionStatus::Cancelled->value, 'failure_reason' => 'Digantikan instruksi transfer baru']);

        return PaymentTransaction::query()->create([
            'university_id' => $student->university_id,
            'transaction_number' => $this->numbering->next('transaction', null, $student->university_id),
            'student_id' => $student->id,
            'provider' => 'manual',
            'method' => PaymentMethod::ManualTransfer,
            'target_type' => $type,
            'target_id' => $id,
            'amount' => Money::fromCents($cents + ($uniqueCode ?? 0) * 100),
            'unique_code' => $uniqueCode,
            'status' => TransactionStatus::Pending,
            'recorded_by' => $actor->id,
            'expires_at' => now()->addDays(3),
            'reconciliation_status' => ReconciliationStatus::Unreconciled,
            'instructions' => ['bank_accounts' => (array) $this->settings->get('keu.bank_accounts')],
        ]);
    }

    public function submitProof(PaymentTransaction $transaction, FileUpload $file, string $senderBank, string $senderName, string $transferDate, string $amount, User $actor): PaymentProof
    {
        return DB::transaction(function () use ($transaction, $file, $senderBank, $senderName, $transferDate, $amount): PaymentProof {
            $tx = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($tx->method !== PaymentMethod::ManualTransfer || ! in_array($tx->status, [TransactionStatus::Pending, TransactionStatus::Failed], true)) {
                throw new ConflictException('Bukti hanya dapat diunggah untuk transfer manual yang menunggu atau ditolak.');
            }

            if (CarbonImmutable::parse($transferDate)->isFuture()) {
                throw new ConflictException('Tanggal transfer tidak boleh di masa depan.');
            }

            $duplicate = $file->checksum !== null && PaymentProof::query()->where('checksum', $file->checksum)->where('payment_transaction_id', '!=', $tx->id)->exists();
            $proof = PaymentProof::query()->updateOrCreate(['payment_transaction_id' => $tx->id], [
                'university_id' => $tx->university_id,
                'file_upload_id' => $file->id,
                'sender_bank' => $senderBank,
                'sender_name' => $senderName,
                'transfer_date' => $transferDate,
                'transferred_amount' => Money::normalize($amount),
                'checksum' => $file->checksum,
                'status' => ProofStatus::Submitted,
                'reason_code' => null,
                'note' => null,
                'duplicate_warning' => $duplicate,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);

            app(FileAttacher::class)->attach($file, $proof);
            $tx->forceFill(['status' => TransactionStatus::AwaitingVerification, 'expires_at' => null, 'lock_version' => $tx->lock_version + 1])->save();

            return $proof;
        });
    }

    public function approve(PaymentProof $proof, User $actor, ?string $actualAmount, ?string $note): PaymentTransaction
    {
        // Persetujuan bukti & posting dalam satu transaksi: bila posting gagal, bukti tetap `submitted`.
        return DB::transaction(function () use ($proof, $actor, $actualAmount, $note): PaymentTransaction {
            [$proof, $tx] = $this->lockReviewable($proof, $actor);
            $amount = $actualAmount !== null && $actualAmount !== '' ? Money::normalize($actualAmount) : (string) $proof->transferred_amount;
            $proof->forceFill(['status' => ProofStatus::Approved, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'note' => $note])->save();
            $tx->forceFill(['verified_by' => $actor->id, 'verified_at' => now()])->save();
            $this->audit->recordAction($tx, AuditAction::Verified, ['amount' => $amount, 'proof' => $proof->id], $note);

            return $this->posting->settle($tx, $amount, CarbonImmutable::parse($proof->transfer_date)->setTime(12, 0), $actor);
        });
    }

    public function reject(PaymentProof $proof, User $actor, string $reasonCode, ?string $note): PaymentProof
    {
        [$proof, $tx] = DB::transaction(function () use ($proof, $actor, $reasonCode, $note): array {
            [$proof, $tx] = $this->lockReviewable($proof, $actor);
            $proof->forceFill(['status' => ProofStatus::Rejected, 'reason_code' => $reasonCode, 'note' => $note, 'reviewed_by' => $actor->id, 'reviewed_at' => now()])->save();
            $tx->forceFill(['status' => TransactionStatus::Failed, 'failure_reason' => self::REJECT_REASONS[$reasonCode] ?? $reasonCode, 'verified_by' => $actor->id, 'verified_at' => now()])->save();

            return [$proof, $tx];
        });

        $this->audit->recordAction($tx, AuditAction::Rejected, ['reason_code' => $reasonCode], $note);
        $this->notifier->student($tx->student, 'keu.proof_rejected', [
            'amount' => Money::format($proof->transferred_amount),
            'reason' => (self::REJECT_REASONS[$reasonCode] ?? $reasonCode).($note ? " — {$note}" : ''),
        ]);

        return $proof;
    }

    /** @return array{0: PaymentProof, 1: PaymentTransaction} */
    private function lockReviewable(PaymentProof $proof, User $actor): array
    {
        $proof = PaymentProof::query()->lockForUpdate()->findOrFail($proof->id);
        $tx = PaymentTransaction::query()->lockForUpdate()->findOrFail($proof->payment_transaction_id);

        if ($proof->status !== ProofStatus::Submitted || $tx->status !== TransactionStatus::AwaitingVerification) {
            throw new ConflictException('Bukti ini sudah diputuskan.', 'ALREADY_DECIDED');
        }

        $student = Student::query()->find($tx->student_id);
        Approvals::assertDistinct($actor, [$student?->user_id, $tx->recorded_by], 'Pengunggah bukti tidak dapat memverifikasi buktinya sendiri (pemisahan tugas).');

        return [$proof, $tx];
    }
}
