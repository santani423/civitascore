<?php

namespace Modules\Finance\Services;

use Modules\Academic\Models\Student;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Models\Payment;
use Modules\Finance\Support\Money;

/**
 * Kartu piutang mahasiswa (FR-RFD-01): kronologi baris tagihan (biaya +,
 * potongan/penyesuaian ±), alokasi pembayaran (−), pembalikan (+), dan
 * pembatalan tagihan, dengan saldo berjalan = piutang. Deposit dilaporkan
 * terpisah (bukan pengurang piutang sebelum dipakai).
 */
class StatementService
{
    /** @return array{student_id: string, entries: array<int, array<string, mixed>>, balance: string} */
    public function forStudent(Student $student): array
    {
        $invoices = Invoice::query()->where('student_id', $student->id)->where('status', '!=', InvoiceStatus::Draft)->get()->keyBy('id');
        $entries = [];

        $items = InvoiceItem::query()->whereIn('invoice_id', $invoices->keys())->get();

        // Tagihan lama tanpa rincian (posting PMB/data awal) dicatat sebagai satu baris senilai neto.
        foreach ($invoices->except($items->pluck('invoice_id')->unique()->all()) as $invoice) {
            $entries[] = ['date' => ($invoice->issued_at ?? $invoice->created_at)?->toIso8601String(), 'invoice_number' => $invoice->invoice_number ?? $invoice->period,
                'type' => 'Tagihan', 'description' => $invoice->period, 'cents' => Money::toCents($invoice->amount)];
        }

        foreach ($items as $item) {
            $invoice = $invoices[$item->invoice_id];
            $entries[] = ['date' => ($item->created_at ?? $invoice->issued_at)?->toIso8601String(), 'invoice_number' => $invoice->invoice_number, 'type' => $item->line_type->label(),
                'description' => $item->description, 'cents' => Money::toCents($item->amount)];
        }

        foreach (Payment::query()->whereIn('invoice_id', $invoices->keys())->get() as $payment) {
            $invoice = $invoices[$payment->invoice_id];
            $cents = Money::toCents($payment->amount);
            $entries[] = ['date' => ($payment->effective_at ?? $payment->created_at)?->toIso8601String(), 'invoice_number' => $invoice->invoice_number,
                'type' => $payment->allocation_type === 'credit_out' ? 'Kelebihan ke deposit' : ($payment->allocation_type === 'deposit_usage' ? 'Pembayaran (deposit)' : 'Pembayaran'),
                'description' => $payment->reference, 'cents' => -$cents];

            if ($payment->status === Payment::REVERSED) {
                $entries[] = ['date' => $payment->reversed_at?->toIso8601String(), 'invoice_number' => $invoice->invoice_number, 'type' => 'Pembalikan pembayaran',
                    'description' => $payment->reversal_reason, 'cents' => $cents];
            }
        }

        foreach ($invoices as $invoice) {
            if ($invoice->status === InvoiceStatus::Cancelled) {
                $entries[] = ['date' => $invoice->cancelled_at?->toIso8601String(), 'invoice_number' => $invoice->invoice_number, 'type' => 'Tagihan dibatalkan',
                    'description' => $invoice->cancel_reason, 'cents' => -Money::toCents($invoice->amount)];
            }
        }

        usort($entries, fn ($a, $b) => strcmp((string) $a['date'], (string) $b['date']));
        $balance = 0;

        foreach ($entries as &$entry) {
            $balance += $entry['cents'];
            $entry['amount'] = Money::fromCents($entry['cents']);
            $entry['balance'] = Money::fromCents($balance);
            unset($entry['cents']);
        }

        return ['student_id' => $student->id, 'entries' => $entries, 'balance' => Money::fromCents($balance)];
    }
}
