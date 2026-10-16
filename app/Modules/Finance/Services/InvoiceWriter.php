<?php

namespace Modules\Finance\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Support\FinanceNumbering;
use Modules\Finance\Support\Money;

/**
 * Pembuat tagihan tunggal (billing run, tagihan manual, SKS, denda, modul
 * lain — kontrak InvoiceIssuer FR-TAG-05). Baris bertanda: biaya positif,
 * potongan negatif. Status awal dihitung InvoiceLedger.
 */
class InvoiceWriter
{
    public function __construct(
        private readonly InvoiceLedger $ledger,
        private readonly FinanceNumbering $numbering,
        private readonly FinanceNotifier $notifier,
    ) {}

    /**
     * @param  array<int, array{line_type: InvoiceLineType, description: string, amount_cents: int, fee_component_id?: ?string, quantity?: float, unit_cents?: int, source_type?: ?string, source_id?: ?string, active_key?: ?string}>  $lines
     * @param  array<string, mixed>  $extra
     */
    public function create(Student $student, ?AcademicTerm $term, BillGroup $group, array $lines, CarbonInterface $dueDate, ?User $actor, bool $draft = false, array $extra = []): Invoice
    {
        $invoice = DB::transaction(function () use ($student, $term, $group, $lines, $dueDate, $actor, $draft, $extra): Invoice {
            $invoice = Invoice::query()->create([
                'university_id' => $student->university_id,
                'student_id' => $student->id,
                'academic_term_id' => $term?->id,
                'invoice_number' => $this->numbering->next('invoice', $this->numberKey($term), $student->university_id),
                'period' => $extra['period'] ?? ($term?->label() ?? now()->format('Y')),
                'bill_group' => $group,
                'amount' => 0,
                'paid_amount' => 0,
                'gross_amount' => 0,
                'status' => $draft ? InvoiceStatus::Draft : InvoiceStatus::Unpaid,
                'due_date' => $dueDate->toDateString(),
                'issued_at' => $draft ? null : now(),
                'created_by' => $actor?->id,
                ...array_intersect_key($extra, array_flip(['parent_invoice_id', 'billing_run_id', 'rate_snapshot', 'notes', 'source_type', 'source_id'])),
            ]);

            foreach (array_values($lines) as $index => $line) {
                $this->addLine($invoice, $line, $index);
            }

            return $this->ledger->recalculate($invoice);
        });

        if (! $draft) {
            $this->announce($invoice, $student);
        }

        return $invoice;
    }

    /** @param  array<string, mixed>  $line */
    public function addLine(Invoice $invoice, array $line, int $sort = 0): InvoiceItem
    {
        $cents = (int) $line['amount_cents'];
        $quantity = (float) ($line['quantity'] ?? 1);

        return InvoiceItem::query()->create([
            'university_id' => $invoice->university_id,
            'invoice_id' => $invoice->id,
            'line_type' => $line['line_type'],
            'fee_component_id' => $line['fee_component_id'] ?? null,
            'description' => mb_substr($line['description'], 0, 255),
            'quantity' => $quantity,
            'unit_amount' => Money::fromCents((int) ($line['unit_cents'] ?? $cents)),
            'amount' => Money::fromCents($cents),
            'source_type' => $line['source_type'] ?? null,
            'source_id' => $line['source_id'] ?? null,
            'active_key' => $line['active_key'] ?? null,
            'sort_order' => $line['sort_order'] ?? $sort,
        ]);
    }

    public function issueDraft(Invoice $invoice): Invoice
    {
        $invoice = DB::transaction(function () use ($invoice): Invoice {
            $locked = $this->ledger->lock($invoice->id);
            $locked->forceFill(['status' => InvoiceStatus::Unpaid, 'issued_at' => now()])->save();

            return $this->ledger->recalculate($locked);
        });

        $this->announce($invoice, $invoice->student);

        return $invoice;
    }

    private function announce(Invoice $invoice, Student $student): void
    {
        if ($invoice->status === InvoiceStatus::Unpaid || $invoice->status === InvoiceStatus::Partial) {
            $this->notifier->student($student, 'keu.invoice_issued', [
                'number' => (string) $invoice->invoice_number,
                'period' => $invoice->period,
                'amount' => Money::format($invoice->amount),
                'due_date' => $invoice->due_date->format('d-m-Y'),
            ]);
        }
    }

    private function numberKey(?AcademicTerm $term): string
    {
        if ($term === null) {
            return now()->format('Y').'0';
        }

        return $term->startYear().($term->semester->value === 'ganjil' ? '1' : '2');
    }
}
