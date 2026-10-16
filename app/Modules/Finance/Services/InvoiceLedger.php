<?php

namespace Modules\Finance\Services;

use Carbon\CarbonImmutable;
use Modules\Finance\Enums\InstallmentStatus;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Events\StudentFinanceChanged;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\Payment;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;

/**
 * Satu-satunya tempat status tagihan & termin dihitung (BR-KEU-09): status
 * adalah turunan dari baris tagihan dan alokasi `posted`. Dipanggil di
 * dalam transaksi pemanggil, setelah tagihan dikunci.
 */
class InvoiceLedger
{
    private const DISCOUNT_LINES = [InvoiceLineType::Aid, InvoiceLineType::Discount, InvoiceLineType::Subsidy, InvoiceLineType::Voucher];

    private const ADJUSTMENT_LINES = [InvoiceLineType::Waiver, InvoiceLineType::Adjustment];

    public function __construct(private readonly KeuSettings $settings) {}

    public function lock(string $invoiceId): Invoice
    {
        return Invoice::query()->lockForUpdate()->findOrFail($invoiceId);
    }

    /** Hitung ulang nominal, dibayar, status, termin, & tunggakan. */
    public function recalculate(Invoice $invoice): Invoice
    {
        $previous = $invoice->status;
        $items = $invoice->items()->get();

        if ($items->isNotEmpty()) {
            $gross = 0;
            $discount = 0;
            $adjustment = 0;

            foreach ($items as $item) {
                $cents = Money::toCents($item->amount);

                if (in_array($item->line_type, self::DISCOUNT_LINES, true)) {
                    $discount -= $cents;
                } elseif (in_array($item->line_type, self::ADJUSTMENT_LINES, true)) {
                    $adjustment += $cents;
                } else {
                    $gross += $cents;
                }
            }

            $invoice->gross_amount = Money::fromCents($gross);
            $invoice->discount_amount = Money::fromCents($discount);
            $invoice->adjustment_amount = Money::fromCents($adjustment);
            $invoice->amount = Money::fromCents($gross - $discount + $adjustment);
        }

        $paid = (int) Payment::query()->where('invoice_id', $invoice->id)->where('status', Payment::POSTED)->get(['amount'])
            ->sum(fn (Payment $p) => Money::toCents($p->amount));
        $invoice->paid_amount = Money::fromCents($paid);
        $amount = Money::toCents($invoice->amount);

        if (! in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Cancelled], true)) {
            $hasWaiver = $items->contains(fn ($i) => $i->line_type === InvoiceLineType::Waiver);
            $invoice->status = match (true) {
                $amount <= 0 && $paid === 0 && $hasWaiver => InvoiceStatus::Waived,
                $paid >= $amount => InvoiceStatus::Paid,
                $paid > 0 => InvoiceStatus::Partial,
                default => InvoiceStatus::Unpaid,
            };
        }

        $invoice->settled_at = $invoice->status === InvoiceStatus::Paid ? ($invoice->settled_at ?? now()) : null;
        $this->recalculateInstallments($invoice);
        $this->refreshOverdue($invoice);
        $invoice->lock_version = ($invoice->lock_version ?? 0) + 1;
        $invoice->save();

        if ($previous !== $invoice->status || $invoice->wasChanged(['amount', 'paid_amount', 'is_overdue'])) {
            StudentFinanceChanged::dispatch($invoice->student_id, $invoice->university_id);
        }

        return $invoice;
    }

    public function recalculateInstallments(Invoice $invoice): void
    {
        $installments = InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')->get();

        foreach ($installments as $installment) {
            $paid = (int) Payment::query()->where('invoice_installment_id', $installment->id)->where('status', Payment::POSTED)->get(['amount'])
                ->sum(fn (Payment $p) => Money::toCents($p->amount));
            $amount = Money::toCents($installment->amount);
            $status = match (true) {
                $paid >= $amount => InstallmentStatus::Paid,
                $paid > 0 => InstallmentStatus::Partial,
                default => InstallmentStatus::Scheduled,
            };

            $installment->forceFill([
                'paid_amount' => Money::fromCents($paid),
                'status' => $status,
                'paid_at' => $status === InstallmentStatus::Paid ? ($installment->paid_at ?? now()) : null,
            ])->save();
        }
    }

    /**
     * Tunggakan = sisa > 0 dan (jatuh tempo + tenggang) terlewati, per
     * tagihan atau per termin aktif (BR-KEU-35). Disimpan sebagai penanda
     * untuk kinerja; tetap turunan.
     */
    public function refreshOverdue(Invoice $invoice, ?CarbonImmutable $today = null): void
    {
        $today ??= CarbonImmutable::today();
        $grace = $this->settings->int('keu.overdue.grace_days');

        if (! $invoice->status->isOpen()) {
            $invoice->is_overdue = false;
            $invoice->overdue_since = null;

            return;
        }

        $installments = InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')->orderBy('seq')->get();
        $since = null;

        if ($installments->isNotEmpty()) {
            foreach ($installments as $installment) {
                $remaining = Money::toCents($installment->amount) - Money::toCents($installment->paid_amount);
                $limit = CarbonImmutable::parse($installment->due_date)->addDays($grace);

                if ($remaining > 0 && $limit->lessThan($today)) {
                    $since = $limit->addDay();
                    break;
                }
            }
        } else {
            $limit = CarbonImmutable::parse($invoice->due_date)->addDays($grace);

            if ($limit->lessThan($today)) {
                $since = $limit->addDay();
            }
        }

        $invoice->is_overdue = $since !== null;
        $invoice->overdue_since = $since?->toDateString();
    }

    /**
     * Jumlah termin jadwal aktif harus sama dengan neto tagihan: selisih
     * (akibat penyesuaian/voucher) diserap termin belum lunas dari belakang.
     */
    public function rebalanceInstallments(Invoice $invoice): void
    {
        $installments = InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')->orderByDesc('seq')->get();

        if ($installments->isEmpty()) {
            return;
        }

        // Pembayaran yang tidak tertaut termin aktif (sebelum jadwal dibuat / jadwal lama) sudah mengurangi kewajiban.
        $activeIds = $installments->pluck('id')->all();
        $paidOutside = (int) Payment::query()->where('invoice_id', $invoice->id)->where('status', Payment::POSTED)
            ->where(fn ($q) => $q->whereNull('invoice_installment_id')->orWhereNotIn('invoice_installment_id', $activeIds))
            ->get(['amount'])->sum(fn (Payment $p) => Money::toCents($p->amount));
        $diff = Money::toCents($invoice->amount) - $paidOutside - $installments->sum(fn ($i) => Money::toCents($i->amount));

        foreach ($installments as $installment) {
            if ($diff === 0) {
                break;
            }

            $amount = Money::toCents($installment->amount);
            $floor = Money::toCents($installment->paid_amount);
            $new = max($floor, $amount + $diff);
            $diff -= ($new - $amount);
            $installment->forceFill(['amount' => Money::fromCents($new)])->save();
        }
    }
}
