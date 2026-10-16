<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PenaltyAccrualStatus;
use Modules\Finance\Enums\PenaltyCalcType;
use Modules\Finance\Enums\PenaltyPeriodUnit;
use Modules\Finance\Enums\PenaltyRuleStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PenaltyAccrual;
use Modules\Finance\Models\PenaltyRule;
use Modules\Finance\Support\Approvals;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;

/**
 * Denda keterlambatan (F-08, BR-KEU-36..40). Denda berjalan dihitung per
 * tagihan/termin terlambat; saat pokok lunas, denda dihitung sampai tanggal
 * bayar efektif (bukan waktu verifikasi) lalu dikristalisasi menjadi
 * tagihan `penalty` terpisah. Denda tidak dihitung atas denda. Satu
 * accrual per (tagihan, termin, aturan) mencegah denda ganda.
 */
class PenaltyService
{
    public function __construct(
        private readonly KeuSettings $settings,
        private readonly FinanceNotifier $notifier,
        private readonly AuditLogService $audit,
        private readonly InvoiceWriter $writer,
    ) {}

    public function submit(PenaltyRule $rule, User $actor): PenaltyRule
    {
        if ($rule->status !== PenaltyRuleStatus::Draft) {
            throw new ConflictException('Hanya aturan berstatus draf yang dapat diajukan.');
        }

        $rule->forceFill(['status' => PenaltyRuleStatus::PendingApproval, 'submitted_by' => $actor->id])->save();

        return $rule;
    }

    public function approve(PenaltyRule $rule, User $actor): PenaltyRule
    {
        if ($rule->status !== PenaltyRuleStatus::PendingApproval) {
            throw new ConflictException('Aturan ini tidak sedang menunggu persetujuan.');
        }

        Approvals::assertDistinct($actor, [$rule->submitted_by, $rule->created_by], 'Penyusun aturan denda tidak dapat menyetujuinya sendiri (pemisahan tugas).');
        $rule->forceFill(['status' => PenaltyRuleStatus::Active, 'approved_by' => $actor->id, 'approved_at' => now()])->save();
        $this->audit->recordAction($rule, AuditAction::Approved, ['code' => $rule->code]);

        return $rule;
    }

    /** Hitung denda berjalan semua tagihan terlambat (job harian). */
    public function accrueAll(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $rules = $this->activeRules($today);

        if ($rules->isEmpty()) {
            return 0;
        }

        $count = 0;
        $groups = $rules->flatMap(fn (PenaltyRule $r) => $r->bill_groups)->unique()->values()->all();

        Invoice::query()->whereIn('status', InvoiceStatus::open())->whereIn('bill_group', $groups)
            ->where('bill_group', '!=', BillGroup::Penalty->value)->where('due_date', '<', $today->toDateString())
            ->chunkById(200, function ($invoices) use ($rules, $today, &$count): void {
                foreach ($invoices as $invoice) {
                    foreach ($this->rulesFor($rules, $invoice) as $rule) {
                        foreach ($this->targets($invoice) as [$installment, $due, $principal, $original]) {
                            $late = $this->lateDays($rule, $due, $today);

                            if ($late <= 0 || $principal <= 0) {
                                continue;
                            }

                            $this->upsertAccrual($invoice, $installment, $rule, $due, $today, $this->amount($rule, $principal, $original, $late));
                            $count++;
                        }
                    }
                }
            });

        return $count;
    }

    /**
     * Dipanggil setelah pembayaran: target yang kini lunas dan dibayar
     * terlambat → kristalisasi denda dengan tanggal bayar efektif.
     */
    public function onPrincipalPaid(Invoice $invoice, CarbonImmutable $paidAt): void
    {
        if ($invoice->bill_group === BillGroup::Penalty) {
            return;
        }

        $rules = $this->rulesFor($this->activeRules($paidAt), $invoice);

        foreach ($rules as $rule) {
            foreach ($this->paidTargets($invoice) as [$installment, $due, $original]) {
                $key = $this->key($invoice, $installment, $rule);
                $existing = PenaltyAccrual::query()->where('active_key', $key)->first();

                if ($existing !== null && $existing->status !== PenaltyAccrualStatus::Accruing) {
                    continue;
                }

                $late = $this->lateDays($rule, $due, $paidAt->startOfDay());
                $amount = $late > 0 ? $this->amount($rule, $original, $original, $late) : 0;

                if ($amount <= 0) {
                    $existing?->forceFill(['status' => PenaltyAccrualStatus::Stopped, 'last_calculated_on' => $paidAt->toDateString()])->save();

                    continue;
                }

                $accrual = $this->upsertAccrual($invoice, $installment, $rule, $due, $paidAt, $amount);
                $this->crystallize($accrual, null);
            }
        }
    }

    /** Ubah denda berjalan menjadi tagihan denda (cut-off akhir semester / manual). */
    public function crystallize(PenaltyAccrual $accrual, ?User $actor): ?Invoice
    {
        if ($accrual->status !== PenaltyAccrualStatus::Accruing) {
            throw new ConflictException('Denda ini sudah ditagihkan atau dihentikan.');
        }

        if (! Money::isPositive($accrual->accrued_amount)) {
            $accrual->forceFill(['status' => PenaltyAccrualStatus::Stopped])->save();

            return null;
        }

        $penaltyInvoice = DB::transaction(function () use ($accrual, $actor): Invoice {
            $invoice = $accrual->invoice()->with('student', 'academicTerm')->firstOrFail();
            $label = $accrual->installment ? " termin {$accrual->installment->seq}" : '';
            $penalty = $this->writer->create($invoice->student, $invoice->academicTerm, BillGroup::Penalty, [[
                'line_type' => InvoiceLineType::Penalty,
                'description' => "Denda keterlambatan {$invoice->invoice_number}{$label} ({$accrual->periods_counted} hari terlambat, {$accrual->rule->name})",
                'amount_cents' => Money::toCents($accrual->accrued_amount),
                'source_type' => 'penalty_accrual',
                'source_id' => $accrual->id,
                'active_key' => 'penalty:'.$accrual->id,
            ]], CarbonImmutable::today()->addDays($this->settings->int('keu.penalty.due_days')), $actor, extra: [
                'parent_invoice_id' => $invoice->id,
                'period' => $invoice->period,
            ]);

            $accrual->forceFill(['status' => PenaltyAccrualStatus::Crystallized, 'penalty_invoice_id' => $penalty->id])->save();

            return $penalty;
        });

        $this->notifier->student($penaltyInvoice->student, 'keu.penalty_issued', ['amount' => Money::format($penaltyInvoice->amount), 'number' => (string) $accrual->invoice->invoice_number]);

        return $penaltyInvoice;
    }

    public function waiveAccrual(PenaltyAccrual $accrual, string $reason, User $actor): PenaltyAccrual
    {
        if ($accrual->status !== PenaltyAccrualStatus::Accruing) {
            throw new ConflictException('Hanya denda berjalan yang dapat dihapus di sini; denda yang sudah ditagihkan dihapus lewat penyesuaian "Pembebasan Denda".');
        }

        $accrual->forceFill(['status' => PenaltyAccrualStatus::Waived])->save();
        $this->audit->recordAction($accrual, AuditAction::ForcedOverride, ['amount' => (string) $accrual->accrued_amount], $reason);

        return $accrual;
    }

    /** @return Collection<int, PenaltyRule> */
    public function activeRules(CarbonImmutable $date): Collection
    {
        return PenaltyRule::query()->where('status', PenaltyRuleStatus::Active)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date->toDateString()))
            ->get();
    }

    public function amount(PenaltyRule $rule, int $principal, int $original, int $lateDays): int
    {
        $periods = match ($rule->period_unit) {
            PenaltyPeriodUnit::Once => 1,
            PenaltyPeriodUnit::Day => $lateDays,
            PenaltyPeriodUnit::Month => (int) ceil($lateDays / 30),
        };

        if ($rule->max_periods !== null) {
            $periods = min($periods, $rule->max_periods);
        }

        $unit = max(1, $this->settings->int('keu.rounding.unit')) * 100;
        $amount = match ($rule->calc_type) {
            PenaltyCalcType::Flat => Money::toCents($rule->flat_amount) * $periods,
            PenaltyCalcType::Percent => Money::roundToUnit($principal * (float) $rule->rate_percent / 100 * $periods, $unit),
        };

        if ($rule->cap_amount !== null) {
            $amount = min($amount, Money::toCents($rule->cap_amount));
        }

        if ($rule->cap_percent !== null) {
            $amount = min($amount, Money::percentCents($original, (float) $rule->cap_percent, $unit));
        }

        return max(0, $amount);
    }

    private function lateDays(PenaltyRule $rule, CarbonImmutable $due, CarbonImmutable $on): int
    {
        return (int) $due->addDays($rule->grace_days)->startOfDay()->diffInDays($on->startOfDay(), false);
    }

    /**
     * @param  Collection<int, PenaltyRule>  $rules
     * @return Collection<int, PenaltyRule>
     */
    private function rulesFor(Collection $rules, Invoice $invoice): Collection
    {
        return $rules->filter(fn (PenaltyRule $r) => in_array($invoice->bill_group->value, $r->bill_groups, true));
    }

    /** @return array<int, array{0: ?InvoiceInstallment, 1: CarbonImmutable, 2: int, 3: int}> [termin, jatuh tempo, sisa pokok, pokok awal] */
    private function targets(Invoice $invoice): array
    {
        $installments = InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')->orderBy('seq')->get();

        if ($installments->isEmpty()) {
            return [[null, CarbonImmutable::parse($invoice->due_date), Money::toCents($invoice->outstanding()), Money::toCents($invoice->amount)]];
        }

        return $installments->map(fn (InvoiceInstallment $i) => [$i, CarbonImmutable::parse($i->due_date), Money::toCents($i->amount) - Money::toCents($i->paid_amount), Money::toCents($i->amount)])->all();
    }

    /** @return array<int, array{0: ?InvoiceInstallment, 1: CarbonImmutable, 2: int}> target yang sudah lunas */
    private function paidTargets(Invoice $invoice): array
    {
        $installments = InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')->orderBy('seq')->get();

        if ($installments->isEmpty()) {
            return $invoice->status === InvoiceStatus::Paid && Payment::query()->where('invoice_id', $invoice->id)->where('status', Payment::POSTED)->exists()
                ? [[null, CarbonImmutable::parse($invoice->due_date), Money::toCents($invoice->amount)]]
                : [];
        }

        return $installments->filter(fn (InvoiceInstallment $i) => Money::compare($i->paid_amount, $i->amount) >= 0)
            ->map(fn (InvoiceInstallment $i) => [$i, CarbonImmutable::parse($i->due_date), Money::toCents($i->amount)])->values()->all();
    }

    private function upsertAccrual(Invoice $invoice, ?InvoiceInstallment $installment, PenaltyRule $rule, CarbonImmutable $due, CarbonImmutable $on, int $amount): PenaltyAccrual
    {
        $key = $this->key($invoice, $installment, $rule);
        $accrual = PenaltyAccrual::query()->firstOrCreate(['active_key' => $key], [
            'university_id' => $invoice->university_id,
            'invoice_id' => $invoice->id,
            'invoice_installment_id' => $installment?->id,
            'penalty_rule_id' => $rule->id,
            'overdue_from' => $due->addDays($rule->grace_days + 1)->toDateString(),
            'status' => PenaltyAccrualStatus::Accruing,
        ]);

        if ($accrual->status === PenaltyAccrualStatus::Accruing) {
            $accrual->forceFill([
                'last_calculated_on' => $on->toDateString(),
                'periods_counted' => max(0, $this->lateDays($rule, $due, $on->startOfDay())),
                'accrued_amount' => Money::fromCents($amount),
            ])->save();
        }

        return $accrual;
    }

    private function key(Invoice $invoice, ?InvoiceInstallment $installment, PenaltyRule $rule): string
    {
        return $invoice->id.':'.($installment?->id ?? '-').':'.$rule->id;
    }
}
