<?php

namespace Modules\Finance\Services;

use App\Support\Http\Exceptions\ConflictException;
use Modules\Academic\Models\Student;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Support\Money;

/** Validasi target pembayaran (tagihan/termin/deposit) milik mahasiswa. */
class TargetResolver
{
    /** @return array{0: string, 1: ?string, 2: int} [jenis target, id, sisa sen] */
    public function resolve(Student $student, string $targetType, ?string $targetId): array
    {
        if ($targetType === PaymentTransaction::TARGET_DEPOSIT) {
            return [PaymentTransaction::TARGET_DEPOSIT, null, 0];
        }

        if ($targetType === PaymentTransaction::TARGET_INSTALLMENT) {
            $installment = InvoiceInstallment::query()->whereNull('superseded_at')->find($targetId);
            $invoice = $installment ? Invoice::query()->find($installment->invoice_id) : null;

            if ($installment === null || $invoice === null || $invoice->student_id !== $student->id) {
                throw new ConflictException('Termin cicilan tidak ditemukan.');
            }

            $remaining = Money::toCents($installment->amount) - Money::toCents($installment->paid_amount);

            if (! $invoice->status->isOpen() || $remaining <= 0) {
                throw new ConflictException('Termin ini sudah lunas.');
            }

            return [PaymentTransaction::TARGET_INSTALLMENT, $installment->id, $remaining];
        }

        $invoice = Invoice::query()->find($targetId);

        if ($invoice === null || $invoice->student_id !== $student->id) {
            throw new ConflictException('Tagihan tidak ditemukan.');
        }

        if (! $invoice->status->isOpen()) {
            throw new ConflictException('Tagihan ini tidak memiliki sisa kewajiban.');
        }

        return [PaymentTransaction::TARGET_INVOICE, $invoice->id, Money::toCents($invoice->outstanding())];
    }
}
