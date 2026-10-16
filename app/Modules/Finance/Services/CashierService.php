<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\CashierSessionStatus;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\ReconciliationStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\CashierSession;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Support\Approvals;
use Modules\Finance\Support\FinanceNumbering;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;

/**
 * Loket/kasir (FR-PMN-01/02): pembayaran tunai hanya dalam sesi kas `open`
 * milik kasir (BR-KEU-49). Tutup sesi mencatat selisih hitung fisik tanpa
 * mengubah transaksi; setoran diverifikasi pihak lain (SOD-KEU-05).
 */
class CashierService
{
    public function __construct(
        private readonly PaymentPostingService $posting,
        private readonly TargetResolver $targets,
        private readonly FinanceNumbering $numbering,
        private readonly KeuSettings $settings,
        private readonly AuditLogService $audit,
    ) {}

    public function open(User $cashier, string $openingFloat): CashierSession
    {
        if (CashierSession::query()->where('cashier_id', $cashier->id)->where('status', CashierSessionStatus::Open)->exists()) {
            throw new ConflictException('Anda masih memiliki sesi kas yang terbuka. Tutup sesi tersebut terlebih dahulu.');
        }

        return CashierSession::query()->create([
            'session_number' => $this->numbering->next('cashier_session'),
            'cashier_id' => $cashier->id,
            'opened_at' => now(),
            'opening_float' => Money::normalize($openingFloat),
            'system_total' => 0,
            'status' => CashierSessionStatus::Open,
        ]);
    }

    public function record(CashierSession $session, Student $student, string $targetType, ?string $targetId, string $amount, ?string $payerName, User $cashier): PaymentTransaction
    {
        return DB::transaction(function () use ($session, $student, $targetType, $targetId, $amount, $payerName, $cashier): PaymentTransaction {
            $session = CashierSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($session->status !== CashierSessionStatus::Open || $session->cashier_id !== $cashier->id) {
                throw new ConflictException('Pembayaran tunai hanya dapat dicatat dalam sesi kas terbuka milik Anda.', 'CASHIER_SESSION_REQUIRED');
            }

            $cents = Money::toCents($amount);
            $limit = Money::toCents((string) $this->settings->get('keu.cash.max_per_transaction'));

            if ($cents <= 0) {
                throw new ConflictException('Nominal pembayaran harus lebih dari nol.');
            }

            if ($limit > 0 && $cents > $limit) {
                throw new ConflictException('Nominal melebihi batas pembayaran tunai per transaksi ('.Money::format(Money::fromCents($limit)).').');
            }

            [$type, $id] = $this->targets->resolve($student, $targetType, $targetId);

            $tx = PaymentTransaction::query()->create([
                'university_id' => $student->university_id,
                'transaction_number' => $this->numbering->next('transaction', null, $student->university_id),
                'student_id' => $student->id,
                'payer_name' => $payerName,
                'provider' => 'cash',
                'method' => PaymentMethod::Cash,
                'target_type' => $type,
                'target_id' => $id,
                'amount' => Money::fromCents($cents),
                'status' => TransactionStatus::Created,
                'cashier_session_id' => $session->id,
                'recorded_by' => $cashier->id,
                'reconciliation_status' => ReconciliationStatus::PendingDeposit,
            ]);

            $session->forceFill(['system_total' => Money::fromCents(Money::toCents($session->system_total) + $cents)])->save();

            // Posting dalam transaksi yang sama: kas sesi & alokasi tidak pernah setengah tercatat.
            return $this->posting->settle($tx, (string) $tx->amount, CarbonImmutable::now(), $cashier);
        });
    }

    public function close(CashierSession $session, string $countedTotal, ?string $note, User $cashier): CashierSession
    {
        return DB::transaction(function () use ($session, $countedTotal, $note, $cashier): CashierSession {
            $session = CashierSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($session->status !== CashierSessionStatus::Open || $session->cashier_id !== $cashier->id) {
                throw new ConflictException('Hanya sesi kas terbuka milik Anda yang dapat ditutup.');
            }

            // Pembalikan transaksi tunai mengurangi kas yang seharusnya ada.
            $reversed = (int) PaymentTransaction::query()->where('cashier_session_id', $session->id)->where('status', TransactionStatus::Reversed)
                ->get(['amount'])->sum(fn (PaymentTransaction $t) => Money::toCents($t->amount));
            $expected = Money::toCents($session->opening_float) + Money::toCents($session->system_total) - $reversed;
            $counted = Money::toCents($countedTotal);

            $session->forceFill([
                'status' => CashierSessionStatus::Closed,
                'closed_at' => now(),
                'counted_total' => Money::fromCents($counted),
                'difference' => Money::fromCents($counted - $expected),
                'note' => $note,
            ])->save();

            $this->audit->recordAction($session, AuditAction::Updated, ['action' => 'close', 'expected' => Money::fromCents($expected), 'counted' => Money::fromCents($counted)], $note);

            return $session;
        });
    }

    public function recordDeposit(CashierSession $session, string $reference, ?string $fileId, User $cashier): CashierSession
    {
        if ($session->status !== CashierSessionStatus::Closed || $session->cashier_id !== $cashier->id) {
            throw new ConflictException('Setoran hanya dapat dicatat untuk sesi milik Anda yang sudah ditutup.');
        }

        $session->forceFill(['deposit_reference' => $reference, 'deposit_file_id' => $fileId])->save();

        return $session;
    }

    public function verify(CashierSession $session, User $actor, ?string $note): CashierSession
    {
        if ($session->status !== CashierSessionStatus::Closed || $session->deposit_reference === null) {
            throw new ConflictException('Sesi harus ditutup dan bukti setoran dicatat sebelum diverifikasi.');
        }

        Approvals::assertDistinct($actor, [$session->cashier_id], 'Kasir tidak dapat memverifikasi setoran sesinya sendiri (pemisahan tugas).');

        $session->forceFill(['status' => CashierSessionStatus::Verified, 'verified_by' => $actor->id, 'verified_at' => now(), 'note' => trim(($session->note ?? '').' '.($note ?? ''))])->save();
        $this->audit->recordAction($session, AuditAction::Verified, ['difference' => (string) $session->difference], $note);

        return $session;
    }
}
