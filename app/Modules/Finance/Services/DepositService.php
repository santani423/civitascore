<?php

namespace Modules\Finance\Services;

use App\Support\Http\Exceptions\ConflictException;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\DepositEntryType;
use Modules\Finance\Models\StudentDepositAccount;
use Modules\Finance\Models\StudentDepositEntry;
use Modules\Finance\Support\Money;

/**
 * Saldo titipan mahasiswa (F-11). Ledger append-only; `balance` = Σ mutasi,
 * `held_amount` = dana ditahan untuk refund. Saldo tersedia (balance − held)
 * tidak pernah negatif (BR-KEU-51) — dijaga row lock akun. Dipanggil di
 * dalam transaksi DB milik pemanggil.
 *
 * Konvensi entri: `amount` = perubahan `balance` (bertanda). Entri hold /
 * release hanya mengubah `held_after` dan mencatat nominal yang ditahan
 * di `amount` bertanda 0 pada balance — nominalnya ada di `note`.
 */
class DepositService
{
    public function account(Student $student, bool $lock = true): StudentDepositAccount
    {
        StudentDepositAccount::query()->firstOrCreate(
            ['university_id' => $student->university_id, 'student_id' => $student->id],
            ['balance' => 0, 'held_amount' => 0],
        );

        $query = StudentDepositAccount::query()->where('student_id', $student->id);

        return $lock ? $query->lockForUpdate()->firstOrFail() : $query->firstOrFail();
    }

    public function available(Student $student): int
    {
        $account = StudentDepositAccount::query()->where('student_id', $student->id)->first();

        return $account ? Money::toCents($account->balance) - Money::toCents($account->held_amount) : 0;
    }

    public function credit(Student $student, int $cents, DepositEntryType $type, ?string $refType, ?string $refId, ?string $note = null, ?string $actorId = null): StudentDepositEntry
    {
        if ($cents <= 0) {
            throw new ConflictException('Nominal deposit harus lebih dari nol.');
        }

        return $this->move($this->account($student), $cents, 0, $type, $refType, $refId, $note, $actorId);
    }

    public function debit(Student $student, int $cents, DepositEntryType $type, ?string $refType, ?string $refId, ?string $note = null, ?string $actorId = null): StudentDepositEntry
    {
        $account = $this->account($student);
        $available = Money::toCents($account->balance) - Money::toCents($account->held_amount);

        if ($cents <= 0 || $cents > $available) {
            throw new ConflictException('Saldo deposit tersedia tidak mencukupi ('.Money::format(Money::fromCents($available)).').', 'INSUFFICIENT_DEPOSIT');
        }

        return $this->move($account, -$cents, 0, $type, $refType, $refId, $note, $actorId);
    }

    public function hold(Student $student, int $cents, string $refType, string $refId, ?string $actorId = null): StudentDepositEntry
    {
        $account = $this->account($student);
        $available = Money::toCents($account->balance) - Money::toCents($account->held_amount);

        if ($cents <= 0 || $cents > $available) {
            throw new ConflictException('Saldo deposit tersedia tidak mencukupi ('.Money::format(Money::fromCents($available)).').', 'INSUFFICIENT_DEPOSIT');
        }

        return $this->move($account, 0, $cents, DepositEntryType::RefundHold, $refType, $refId, 'Ditahan '.Money::format(Money::fromCents($cents)), $actorId);
    }

    public function release(Student $student, int $cents, string $refType, string $refId, ?string $actorId = null): StudentDepositEntry
    {
        return $this->move($this->account($student), 0, -$cents, DepositEntryType::RefundRelease, $refType, $refId, 'Dilepas '.Money::format(Money::fromCents($cents)), $actorId);
    }

    /** Refund selesai: saldo & dana ditahan sama-sama berkurang. */
    public function payout(Student $student, int $cents, string $refType, string $refId, ?string $actorId = null): StudentDepositEntry
    {
        return $this->move($this->account($student), -$cents, -$cents, DepositEntryType::RefundPayout, $refType, $refId, null, $actorId);
    }

    private function move(StudentDepositAccount $account, int $balanceDelta, int $heldDelta, DepositEntryType $type, ?string $refType, ?string $refId, ?string $note, ?string $actorId): StudentDepositEntry
    {
        $balance = Money::toCents($account->balance) + $balanceDelta;
        $held = Money::toCents($account->held_amount) + $heldDelta;

        if ($balance < 0 || $held < 0 || $balance - $held < 0) {
            throw new ConflictException('Mutasi deposit ditolak: saldo menjadi negatif.', 'INSUFFICIENT_DEPOSIT');
        }

        $account->forceFill([
            'balance' => Money::fromCents($balance),
            'held_amount' => Money::fromCents($held),
            'lock_version' => $account->lock_version + 1,
        ])->save();

        return StudentDepositEntry::query()->create([
            'university_id' => $account->university_id,
            'account_id' => $account->id,
            'entry_type' => $type,
            'amount' => Money::fromCents($balanceDelta),
            'balance_after' => Money::fromCents($balance),
            'held_after' => Money::fromCents($held),
            'reference_type' => $refType,
            'reference_id' => $refId,
            'note' => $note,
            'created_by' => $actorId,
        ]);
    }
}
