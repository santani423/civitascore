<?php

namespace Modules\Finance\Support;

use Carbon\CarbonImmutable;
use Modules\Academic\Models\AcademicBlock;
use Modules\Academic\Models\AcademicBlockExemption;
use Modules\Academic\Models\Student;
use Modules\Finance\Models\FinancialAidAward;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceAdjustment;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Models\Refund;

/**
 * Bentuk JSON KEU yang dipakai admin & portal mahasiswa. Uang selalu
 * string desimal; status disertai label Indonesia. `$forStudent`
 * menyembunyikan data internal (catatan petugas, pencatat).
 */
final class Presenter
{
    /** @return array<string, mixed>|null */
    public static function student(?Student $student): ?array
    {
        if ($student === null) {
            return null;
        }

        return [
            'id' => $student->id,
            'nim' => $student->nim,
            'name' => $student->name,
            'study_program' => $student->relationLoaded('studyProgram') ? $student->studyProgram?->name : null,
            'admission_year' => $student->admission_year,
            'status' => $student->status?->value,
            'status_label' => $student->status?->label(),
        ];
    }

    /** @return array<string, mixed> */
    public static function invoice(Invoice $invoice, bool $detail = false, bool $forStudent = false): array
    {
        $data = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'student' => $invoice->relationLoaded('student') ? self::student($invoice->student) : null,
            'student_id' => $invoice->student_id,
            'academic_term_id' => $invoice->academic_term_id,
            'period' => $invoice->period,
            'bill_group' => $invoice->bill_group->value,
            'bill_group_label' => $invoice->bill_group->label(),
            'gross_amount' => (string) $invoice->gross_amount,
            'discount_amount' => (string) $invoice->discount_amount,
            'adjustment_amount' => (string) $invoice->adjustment_amount,
            'amount' => (string) $invoice->amount,
            'paid_amount' => (string) $invoice->paid_amount,
            'outstanding' => $invoice->outstanding(),
            'status' => $invoice->status->value,
            'status_label' => $invoice->status->label(),
            'is_overdue' => (bool) $invoice->is_overdue,
            'overdue_since' => $invoice->overdue_since?->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'issued_at' => $invoice->issued_at?->toIso8601String(),
            'settled_at' => $invoice->settled_at?->toIso8601String(),
            'parent_invoice_id' => $invoice->parent_invoice_id,
            'source_type' => $invoice->source_type,
            'created_at' => $invoice->created_at?->toIso8601String(),
        ];

        if ($detail) {
            $data += [
                'items' => $invoice->items->map(fn (InvoiceItem $i) => [
                    'id' => $i->id, 'line_type' => $i->line_type->value, 'line_type_label' => $i->line_type->label(), 'description' => $i->description,
                    'quantity' => (string) $i->quantity, 'unit_amount' => (string) $i->unit_amount, 'amount' => (string) $i->amount,
                ])->values(),
                'installments' => $invoice->installments->map(fn (InvoiceInstallment $i) => self::installment($i))->values(),
                'payments' => $invoice->payments->map(fn (Payment $p) => self::allocation($p))->values(),
                'adjustments' => $forStudent ? [] : $invoice->adjustments->map(fn (InvoiceAdjustment $a) => self::adjustment($a))->values(),
                'penalty_invoices' => $invoice->penaltyInvoices->map(fn (Invoice $p) => self::invoice($p))->values(),
                'cancel_reason' => $invoice->cancel_reason,
                'rate_snapshot' => $forStudent ? null : $invoice->rate_snapshot,
                'notes' => $forStudent ? null : $invoice->notes,
            ];
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public static function installment(InvoiceInstallment $installment): array
    {
        $remaining = Money::sub($installment->amount, $installment->paid_amount);
        $overdue = Money::isPositive($remaining) && CarbonImmutable::parse($installment->due_date)->lessThan(CarbonImmutable::today());

        return [
            'id' => $installment->id,
            'invoice_id' => $installment->invoice_id,
            'seq' => $installment->seq,
            'schedule_version' => $installment->schedule_version,
            'amount' => (string) $installment->amount,
            'paid_amount' => (string) $installment->paid_amount,
            'remaining' => $remaining,
            'due_date' => $installment->due_date->toDateString(),
            'status' => $installment->status->value,
            'status_label' => $overdue ? 'Terlambat' : $installment->status->label(),
            'is_overdue' => $overdue,
        ];
    }

    /** @return array<string, mixed> */
    public static function allocation(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'payment_transaction_id' => $payment->payment_transaction_id,
            'invoice_installment_id' => $payment->invoice_installment_id,
            'amount' => (string) $payment->amount,
            'allocation_type' => $payment->allocation_type,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at?->toDateString(),
            'method' => $payment->method,
            'reference' => $payment->reference,
            'reversal_reason' => $payment->reversal_reason,
        ];
    }

    /** @return array<string, mixed> */
    public static function adjustment(InvoiceAdjustment $adjustment): array
    {
        return [
            'id' => $adjustment->id,
            'adjustment_number' => $adjustment->adjustment_number,
            'invoice_id' => $adjustment->invoice_id,
            'invoice_number' => $adjustment->relationLoaded('invoice') ? $adjustment->invoice?->invoice_number : null,
            'student' => $adjustment->relationLoaded('invoice') && $adjustment->invoice?->relationLoaded('student') ? self::student($adjustment->invoice->student) : null,
            'type' => $adjustment->type->value,
            'type_label' => $adjustment->type->label(),
            'amount' => (string) $adjustment->amount,
            'reason_code' => $adjustment->reason_code,
            'reason_note' => $adjustment->reason_note,
            'status' => $adjustment->status->value,
            'status_label' => $adjustment->status->label(),
            'requested_by' => $adjustment->relationLoaded('requester') ? $adjustment->requester?->name : $adjustment->requested_by,
            'decided_by' => $adjustment->relationLoaded('decider') ? $adjustment->decider?->name : $adjustment->decided_by,
            'decision_note' => $adjustment->decision_note,
            'created_at' => $adjustment->created_at?->toIso8601String(),
            'applied_at' => $adjustment->applied_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function transaction(PaymentTransaction $tx, bool $forStudent = false): array
    {
        return [
            'id' => $tx->id,
            'transaction_number' => $tx->transaction_number,
            'receipt_number' => $tx->receipt_number,
            'student' => $tx->relationLoaded('student') ? self::student($tx->student) : null,
            'payer_name' => $tx->payer_name,
            'channel' => $tx->relationLoaded('channel') && $tx->channel ? ['id' => $tx->channel->id, 'code' => $tx->channel->code, 'name' => $tx->channel->name] : null,
            'provider' => $tx->provider,
            'method' => $tx->method->value,
            'method_label' => $tx->method->label(),
            'target_type' => $tx->target_type,
            'target_id' => $tx->target_id,
            'amount' => (string) $tx->amount,
            'channel_fee' => (string) $tx->channel_fee,
            'status' => $tx->status->value,
            'status_label' => $tx->status->label(),
            'va_number' => $tx->va_number,
            'qr_string' => $tx->qr_string,
            'redirect_url' => $tx->redirect_url,
            'unique_code' => $tx->unique_code,
            'instructions' => $tx->instructions,
            'expires_at' => $tx->expires_at?->toIso8601String(),
            'paid_at' => $tx->paid_at?->toIso8601String(),
            'reconciliation_status' => $tx->reconciliation_status->value,
            'reconciliation_label' => $tx->reconciliation_status->label(),
            'refunded_amount' => (string) $tx->refunded_amount,
            'failure_reason' => $tx->failure_reason,
            'needs_resolution' => (bool) $tx->needs_resolution,
            'proof' => $tx->relationLoaded('proof') && $tx->proof ? [
                'id' => $tx->proof->id, 'status' => $tx->proof->status->value, 'status_label' => $tx->proof->status->label(), 'sender_bank' => $tx->proof->sender_bank,
                'sender_name' => $tx->proof->sender_name, 'transfer_date' => $tx->proof->transfer_date?->toDateString(), 'transferred_amount' => (string) $tx->proof->transferred_amount,
                'reason_code' => $tx->proof->reason_code, 'note' => $tx->proof->note, 'duplicate_warning' => (bool) $tx->proof->duplicate_warning, 'file_upload_id' => $tx->proof->file_upload_id,
            ] : null,
            'allocations' => $tx->relationLoaded('allocations') ? $tx->allocations->map(fn (Payment $p) => self::allocation($p))->values() : null,
            'recorded_by' => $forStudent ? null : ($tx->relationLoaded('recorder') ? $tx->recorder?->name : $tx->recorded_by),
            'verified_by' => $forStudent ? null : ($tx->relationLoaded('verifier') ? $tx->verifier?->name : $tx->verified_by),
            'reversal_reason' => $tx->reversal_reason,
            'created_at' => $tx->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function award(FinancialAidAward $award): array
    {
        return [
            'id' => $award->id,
            'student' => $award->relationLoaded('student') ? self::student($award->student) : null,
            'policy' => $award->relationLoaded('policy') && $award->policy ? ['id' => $award->policy->id, 'code' => $award->policy->code, 'name' => $award->policy->name, 'kind' => $award->policy->kind->value, 'kind_label' => $award->policy->kind->label()] : null,
            'calc_type' => $award->calc_type->value,
            'calc_type_label' => $award->calc_type->label(),
            'value' => (string) $award->value,
            'max_amount' => $award->max_amount !== null ? (string) $award->max_amount : null,
            'fee_component_ids' => $award->fee_component_ids,
            'start_term' => $award->relationLoaded('startTerm') ? $award->startTerm?->label() : null,
            'end_term' => $award->relationLoaded('endTerm') ? $award->endTerm?->label() : null,
            'start_term_id' => $award->start_term_id,
            'end_term_id' => $award->end_term_id,
            'status' => $award->status->value,
            'status_label' => $award->status->label(),
            'basis' => $award->basis,
            'decision_note' => $award->decision_note,
            'revoke_reason' => $award->revoke_reason,
            'requested_by' => $award->requested_by,
            'created_at' => $award->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function refund(Refund $refund, bool $revealAccount = false): array
    {
        return [
            'id' => $refund->id,
            'refund_number' => $refund->refund_number,
            'student' => $refund->relationLoaded('student') ? self::student($refund->student) : null,
            'amount' => (string) $refund->amount,
            'reason_code' => $refund->reason_code,
            'reason_note' => $refund->reason_note,
            'beneficiary_bank' => $refund->beneficiary_bank,
            'beneficiary_account' => $revealAccount ? $refund->beneficiary_account : AuditMask::mask($refund->beneficiary_account),
            'beneficiary_name' => $refund->beneficiary_name,
            'beneficiary_relation' => $refund->beneficiary_relation,
            'status' => $refund->status->value,
            'status_label' => $refund->status->label(),
            'requested_by' => $refund->requested_by,
            'decision_note' => $refund->decision_note,
            'execution_reference' => $refund->execution_reference,
            'failure_reason' => $refund->failure_reason,
            'created_at' => $refund->created_at?->toIso8601String(),
            'executed_at' => $refund->executed_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function block(AcademicBlock $block, bool $withAmounts = true): array
    {
        return [
            'id' => $block->id,
            'student' => $block->relationLoaded('student') ? self::student($block->student) : null,
            'source' => $block->source,
            'rule_code' => $block->source_rule_code,
            'services' => $block->restricted_services,
            'reason' => $block->reason,
            'shortfall' => $withAmounts ? ($block->details['shortfall'] ?? null) : null,
            'status' => $block->status->value,
            'status_label' => $block->status->label(),
            'effective_from' => $block->effective_from?->toIso8601String(),
            'revoked_at' => $block->revoked_at?->toIso8601String(),
            'revoke_reason' => $block->revoke_reason,
            'exemptions' => $block->relationLoaded('exemptions') ? $block->exemptions->map(fn (AcademicBlockExemption $e) => self::exemption($e))->values() : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function exemption(AcademicBlockExemption $exemption): array
    {
        return [
            'id' => $exemption->id,
            'academic_block_id' => $exemption->academic_block_id,
            'service' => $exemption->service,
            'valid_from' => $exemption->valid_from?->toIso8601String(),
            'valid_until' => $exemption->valid_until?->toIso8601String(),
            'reason' => $exemption->reason,
            'promise_amount' => $exemption->promise_amount !== null ? (string) $exemption->promise_amount : null,
            'promise_date' => $exemption->promise_date?->toDateString(),
            'status' => $exemption->status->value,
            'status_label' => $exemption->status->label(),
            'requested_by' => $exemption->requested_by,
            'decision_note' => $exemption->decision_note,
            'student' => $exemption->relationLoaded('block') && $exemption->block?->relationLoaded('student') ? self::student($exemption->block->student) : null,
            'created_at' => $exemption->created_at?->toIso8601String(),
        ];
    }
}
