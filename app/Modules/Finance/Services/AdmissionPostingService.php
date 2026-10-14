<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;

/**
 * Posting tagihan & pembayaran biaya awal PMB ke Finance (docs/pmb/11 §B.2,
 * KD-03). Idempoten lewat unique (source_type, source_id): satu `invoices`
 * per termin cicilan (`pmb_invoice_term_{n}` + id tagihan PMB), sehingga
 * cekal keuangan berbasis jatuh tempo bekerja di modul Finance.
 */
class AdmissionPostingService
{
    /**
     * @param  array<int, array{seq: int, amount: string, due_date: string}>  $terms  nominal neto per termin (sudah dipotong diskon)
     * @param  array<int, array{reference: string, amount: string, paid_at: string, channel: string}>  $payments  transaksi sukses berurutan
     * @return array<int, string> id invoices Finance
     */
    public function post(string $universityId, string $studentId, string $period, string $pmbInvoiceId, array $terms, array $payments): array
    {
        return DB::transaction(function () use ($universityId, $studentId, $period, $pmbInvoiceId, $terms, $payments): array {
            $invoices = [];

            foreach ($terms as $term) {
                $invoices[] = Invoice::query()->withoutGlobalScopes()->firstOrCreate(
                    ['source_type' => 'pmb_invoice_term_'.$term['seq'], 'source_id' => $pmbInvoiceId],
                    [
                        'university_id' => $universityId,
                        'student_id' => $studentId,
                        'period' => $period,
                        'amount' => $term['amount'],
                        'paid_amount' => 0,
                        'status' => InvoiceStatus::Unpaid,
                        'due_date' => $term['due_date'],
                    ],
                );
            }

            // Alokasikan setiap pembayaran ke termin berurutan (termin terdekat dulu); idempoten per referensi.
            foreach ($payments as $payment) {
                $remaining = $this->cents($payment['amount']);
                $remaining -= $this->cents((string) Payment::query()->withoutGlobalScopes()
                    ->whereIn('invoice_id', array_map(fn (Invoice $i) => $i->getKey(), $invoices))
                    ->where('reference', $payment['reference'])->sum('amount'));

                foreach ($invoices as $invoice) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $open = $this->cents((string) $invoice->amount) - $this->cents((string) $invoice->paid_amount);

                    if ($open <= 0) {
                        continue;
                    }

                    $portion = min($open, $remaining);
                    Payment::query()->create([
                        'university_id' => $universityId,
                        'invoice_id' => $invoice->getKey(),
                        'amount' => $this->money($portion),
                        'paid_at' => substr($payment['paid_at'], 0, 10),
                        'method' => 'pmb',
                        'reference' => $payment['reference'],
                        'channel' => $payment['channel'],
                    ]);

                    $paid = $this->cents((string) $invoice->paid_amount) + $portion;
                    $invoice->forceFill([
                        'paid_amount' => $this->money($paid),
                        'status' => $paid >= $this->cents((string) $invoice->amount) ? InvoiceStatus::Paid : InvoiceStatus::Partial,
                    ])->save();
                    $remaining -= $portion;
                }
            }

            return array_map(fn (Invoice $i) => (string) $i->getKey(), $invoices);
        });
    }

    private function cents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '+')), 2, '0');

        return ((int) $whole) * 100 + (int) substr(str_pad($fraction, 2, '0'), 0, 2);
    }

    private function money(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
