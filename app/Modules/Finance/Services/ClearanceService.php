<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Academic\Enums\AcademicBlockStatus;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicBlock;
use Modules\Academic\Models\AcademicBlockExemption;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\Academic\Services\AcademicBlockService;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\ClearanceRequirement;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PenaltyAccrualStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\FinanceClearanceRule;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Models\PenaltyAccrual;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;

/**
 * Sumber cekal keuangan (FinanceBlockSource, rancangan KEU §10.3). KEU
 * menghitung kekurangan per aturan; SIAKAD (AcademicBlockService) menyimpan
 * & menegakkan cekal. Cekal dicabut otomatis saat syarat terpenuhi
 * (BR-KEU-66). Dispensasi keuangan: petugas ≤ batas hari, di atasnya
 * butuh pejabat (BR-KEU-67).
 */
class ClearanceService
{
    public const SOURCE = 'finance';

    public function __construct(
        private readonly AcademicBlockService $blocks,
        private readonly KeuSettings $settings,
        private readonly FinanceNotifier $notifier,
        private readonly AuditLogService $audit,
    ) {}

    /** @return array<int, array{rule: string, blocked: bool, shortfall: string}> */
    public function evaluateStudent(Student $student, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $rules = $this->rulesFor($student, $today);
        $results = [];
        $term = AcademicTerm::query()->where('is_current', true)->first();

        foreach ($rules as $rule) {
            [$shortfall, $refs] = $this->shortfall($student, $rule, $today, $term);
            $blocked = $student->status === StudentStatus::Active && $shortfall > 0 && $shortfall >= Money::toCents($rule->min_outstanding_amount);
            $existing = $this->blocks->activeBlockFor($student, self::SOURCE, $rule->code);

            if ($blocked) {
                $reason = "Kewajiban keuangan belum terpenuhi ({$rule->name}). Selesaikan pembayaran atau hubungi Bagian Keuangan.";
                $this->blocks->impose($student, self::SOURCE, $rule->code, 'financial', $rule->services, $reason, [
                    'shortfall' => Money::fromCents($shortfall),
                    'requirement' => $rule->requirement->value,
                    'invoice_ids' => $refs,
                ], $term?->id);

                if ($existing === null) {
                    $this->notifier->student($student, 'keu.block_imposed', ['services' => $this->serviceLabels($rule->services), 'reason' => 'kekurangan '.Money::format(Money::fromCents($shortfall)).' ('.$rule->name.')']);
                }
            } elseif ($existing !== null) {
                $this->blocks->revoke($existing, 'Kewajiban keuangan telah dipenuhi (dievaluasi otomatis).');
                $this->notifier->student($student, 'keu.block_revoked', ['rule' => $rule->name]);
            }

            $results[] = ['rule' => $rule->code, 'blocked' => $blocked, 'shortfall' => Money::fromCents($shortfall)];
        }

        // Cekal dari aturan yang kini nonaktif/tidak berlaku dicabut.
        $activeCodes = $rules->pluck('code')->all();
        AcademicBlock::query()->where('student_id', $student->id)->where('source', self::SOURCE)
            ->whereIn('status', [AcademicBlockStatus::Active, AcademicBlockStatus::Exempted])
            ->whereNotIn('source_rule_code', $activeCodes)->get()
            ->each(fn (AcademicBlock $b) => $this->blocks->revoke($b, 'Aturan cekal tidak lagi berlaku.'));

        return $results;
    }

    public function evaluateAll(?CarbonImmutable $today = null): int
    {
        $count = 0;

        Student::query()->where('status', StudentStatus::Active)->chunkById(300, function ($students) use ($today, &$count): void {
            foreach ($students as $student) {
                $this->evaluateStudent($student, $today);
                $count++;
            }
        });

        return $count;
    }

    /** @return Collection<int, FinanceClearanceRule> */
    public function rulesFor(Student $student, CarbonImmutable $today): Collection
    {
        return FinanceClearanceRule::query()->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('active_from')->orWhereDate('active_from', '<=', $today->toDateString()))
            ->where(fn ($q) => $q->whereNull('active_until')->orWhereDate('active_until', '>=', $today->toDateString()))
            ->where(fn ($q) => $q->whereNull('study_program_id')->orWhere('study_program_id', $student->study_program_id))
            ->get();
    }

    /**
     * Kekurangan (sen) menurut aturan + id tagihan rujukan.
     *
     * @return array{0: int, 1: array<int, string>}
     */
    public function shortfall(Student $student, FinanceClearanceRule $rule, CarbonImmutable $today, ?AcademicTerm $term): array
    {
        $groups = $rule->bill_groups ?: null;
        $query = Invoice::query()->where('student_id', $student->id)->whereIn('status', InvoiceStatus::open())
            ->when($groups !== null, fn ($q) => $q->whereIn('bill_group', $groups))
            ->when(! $rule->include_penalties, fn ($q) => $q->where('bill_group', '!=', BillGroup::Penalty->value));

        $query = match ($rule->terms) {
            'current' => $query->when($term, fn ($q) => $q->where('academic_term_id', $term->id), fn ($q) => $q->whereRaw('1 = 0')),
            'previous' => $query->when($term, fn ($q) => $q->where(fn ($w) => $w->whereNull('academic_term_id')->orWhereIn('academic_term_id', AcademicTerm::query()->where('start_date', '<', $term->start_date)->pluck('id')))),
            'current_and_previous' => $query->when($term, fn ($q) => $q->where(fn ($w) => $w->whereNull('academic_term_id')->orWhereIn('academic_term_id', AcademicTerm::query()->where('start_date', '<=', $term->start_date)->pluck('id')))),
            default => $query,
        };

        $invoices = $query->get();
        $total = 0;
        $refs = [];

        foreach ($invoices as $invoice) {
            $cents = $this->invoiceShortfall($invoice, $rule, $today);

            if ($cents > 0) {
                $total += $cents;
                $refs[] = $invoice->id;
            }
        }

        $graceHours = $this->settings->int('keu.clearance.pending_proof_grace_hours');

        if ($graceHours > 0 && $total > 0) {
            $pending = PaymentTransaction::query()->where('student_id', $student->id)->where('status', TransactionStatus::AwaitingVerification)
                ->where('updated_at', '>=', now()->subHours($graceHours))->get()->sum(fn (PaymentTransaction $t) => Money::toCents($t->amount));
            $total = max(0, $total - (int) $pending);
        }

        return [$total, $refs];
    }

    /**
     * Bebas tanggungan keuangan (BR-KEU-70): tidak ada piutang di semua
     * semester (termasuk denda & estimasi denda berjalan) dan tidak ada
     * transaksi menunggu verifikasi.
     *
     * @return array{clear: bool, outstanding: string, accruing_penalty: string, pending_verification: int}
     */
    public function clearance(Student $student): array
    {
        $outstanding = Invoice::query()->where('student_id', $student->id)->whereIn('status', InvoiceStatus::open())->get()
            ->sum(fn (Invoice $i) => Money::toCents($i->outstanding()));
        $accruing = PenaltyAccrual::query()->where('status', PenaltyAccrualStatus::Accruing)
            ->whereIn('invoice_id', Invoice::query()->where('student_id', $student->id)->pluck('id'))->get()
            ->sum(fn (PenaltyAccrual $a) => Money::toCents($a->accrued_amount));
        $pending = PaymentTransaction::query()->where('student_id', $student->id)->where('status', TransactionStatus::AwaitingVerification)->count();

        return [
            'clear' => $outstanding === 0 && $accruing === 0 && $pending === 0,
            'outstanding' => Money::fromCents((int) $outstanding),
            'accruing_penalty' => Money::fromCents((int) $accruing),
            'pending_verification' => $pending,
        ];
    }

    /**
     * Dispensasi cekal keuangan. Mahasiswa selalu menunggu persetujuan;
     * petugas langsung aktif bila ≤ batas hari staf, pejabat (approve) langsung.
     */
    public function requestDispensation(AcademicBlock $block, string $service, CarbonImmutable $validUntil, string $reason, User $actor, bool $byStudent, ?string $promiseAmount = null, ?string $promiseDate = null): AcademicBlockExemption
    {
        if ($block->source !== self::SOURCE) {
            throw new ConflictException('Dispensasi di sini hanya untuk cekal keuangan.');
        }

        $days = (int) ceil(now()->diffInHours($validUntil) / 24);

        if ($days > $this->settings->int('keu.dispensation.max_days')) {
            throw new ConflictException('Dispensasi maksimal '.$this->settings->int('keu.dispensation.max_days').' hari.');
        }

        $auto = ! $byStudent && ($actor->hasPermissionTo('finance_clearance.approve') || $days <= $this->settings->int('keu.dispensation.staff_max_days'));
        $exemption = $this->blocks->requestExemption($block, $service, $validUntil, $reason, $actor, $auto, $promiseAmount, $promiseDate);
        $this->audit->recordAction($block, $auto ? AuditAction::ForcedOverride : AuditAction::Created, ['dispensation' => $exemption->id, 'service' => $service, 'valid_until' => $validUntil->toIso8601String()], $reason);

        if ($auto) {
            $this->notifyExemption($exemption, 'diberikan');
        }

        return $exemption;
    }

    public function decideDispensation(AcademicBlockExemption $exemption, bool $approve, User $actor, ?string $note): AcademicBlockExemption
    {
        $exemption = $this->blocks->decideExemption($exemption, $approve, $actor, $note);
        $this->audit->recordAction($exemption->block, $approve ? AuditAction::Approved : AuditAction::Rejected, ['dispensation' => $exemption->id], $note);
        $this->notifyExemption($exemption, $approve ? 'disetujui' : 'ditolak');

        return $exemption;
    }

    private function invoiceShortfall(Invoice $invoice, FinanceClearanceRule $rule, CarbonImmutable $today): int
    {
        $installments = InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')->orderBy('seq')->get();
        $remaining = fn (InvoiceInstallment $i) => max(0, Money::toCents($i->amount) - Money::toCents($i->paid_amount));

        return match ($rule->requirement) {
            ClearanceRequirement::FullyPaid => Money::toCents($invoice->outstanding()),
            ClearanceRequirement::FirstInstallmentPaid => $installments->isEmpty() ? Money::toCents($invoice->outstanding()) : $remaining($installments->first()),
            ClearanceRequirement::DueItemsPaid => $installments->isEmpty()
                ? (CarbonImmutable::parse($invoice->due_date)->addDays($rule->grace_days)->lessThan($today) ? Money::toCents($invoice->outstanding()) : 0)
                : (int) $installments->filter(fn (InvoiceInstallment $i) => CarbonImmutable::parse($i->due_date)->addDays($rule->grace_days)->lessThan($today))->sum($remaining),
        };
    }

    /** @param  array<int, string>  $services */
    private function serviceLabels(array $services): string
    {
        return implode(', ', array_map(fn ($s) => AcademicBlockService::SERVICES[$s] ?? $s, $services));
    }

    private function notifyExemption(AcademicBlockExemption $exemption, string $status): void
    {
        $student = $exemption->block->student;
        $this->notifier->student($student, 'keu.exemption_decided', [
            'service' => AcademicBlockService::SERVICES[$exemption->service] ?? $exemption->service,
            'status' => $status,
            'until' => $status === 'ditolak' ? '' : ' sampai '.$exemption->valid_until->format('d-m-Y H:i'),
        ]);
    }
}
