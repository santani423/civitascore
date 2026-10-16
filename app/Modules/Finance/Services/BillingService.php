<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\AdjustmentType;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\BillingRunStatus;
use Modules\Finance\Enums\CalcBasis;
use Modules\Finance\Enums\FeeCategory;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\BillingRun;
use Modules\Finance\Models\BillingRunItem;
use Modules\Finance\Models\FeeComponent;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Support\Approvals;
use Modules\Finance\Support\FinanceNumbering;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;
use Throwable;

/**
 * Penerbitan tagihan (F-03): billing run (simulasi → persetujuan → posting,
 * FR-TAG-01), tagihan biaya SKS dari KRS (FR-TAG-02/03), dan tagihan manual
 * (FR-TAG-04). Idempoten lewat `invoice_items.active_key` (BR-KEU-07).
 */
class BillingService
{
    public const SKIP_NO_TABLE = 'NO_RATE_TABLE';

    public const SKIP_NO_RATE = 'NO_RATE';

    public const SKIP_ALREADY_BILLED = 'ALREADY_BILLED';

    public const SKIP_UKT_MISSING = 'UKT_MISSING';

    public const SKIP_ZERO = 'ZERO_AMOUNT';

    public const SKIP_STATUS = 'INELIGIBLE_STATUS';

    public function __construct(
        private readonly TariffResolver $tariffs,
        private readonly AidCalculator $aid,
        private readonly InvoiceWriter $writer,
        private readonly InvoiceLedger $ledger,
        private readonly AdjustmentService $adjustments,
        private readonly FinanceNumbering $numbering,
        private readonly KeuSettings $settings,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  array<int, string>  $componentIds
     * @param  array{study_program_ids?: array<int, string>, admission_years?: array<int, int>, student_ids?: array<int, string>}  $scope
     */
    public function simulate(AcademicTerm $term, BillGroup $group, array $componentIds, array $scope, CarbonImmutable $dueDate, User $actor): BillingRun
    {
        $components = FeeComponent::query()->whereIn('id', $componentIds)->where('is_active', true)->get();

        if ($components->count() !== count(array_unique($componentIds))) {
            throw new ConflictException('Komponen biaya tidak ditemukan atau nonaktif.');
        }

        if ($dueDate->lessThan(CarbonImmutable::today())) {
            throw new ConflictException('Jatuh tempo tidak boleh sebelum hari ini (BR-KEU-13).');
        }

        return DB::transaction(function () use ($term, $group, $components, $scope, $dueDate, $actor): BillingRun {
            $run = BillingRun::query()->create([
                'run_number' => $this->numbering->next('billing_run'),
                'academic_term_id' => $term->id,
                'bill_group' => $group,
                'fee_component_ids' => $components->pluck('id')->values()->all(),
                'scope' => $scope,
                'due_date' => $dueDate->toDateString(),
                'status' => BillingRunStatus::Simulated,
                'created_by' => $actor->id,
            ]);

            $totals = ['students' => 0, 'ready' => 0, 'skipped' => 0, 'gross' => 0, 'discount' => 0, 'net' => 0, 'skip_reasons' => []];

            foreach ($this->eligibleStudents($scope, $components) as $student) {
                $computed = $this->compute($student, $term, $components->all(), $group);
                $totals['students']++;

                if ($computed['skip'] !== null) {
                    $totals['skipped']++;
                    $totals['skip_reasons'][$computed['skip']] = ($totals['skip_reasons'][$computed['skip']] ?? 0) + 1;
                } else {
                    $totals['ready']++;
                    $totals['gross'] += $computed['gross'];
                    $totals['discount'] += $computed['discount'];
                    $totals['net'] += $computed['net'];
                }

                BillingRunItem::query()->create([
                    'billing_run_id' => $run->id,
                    'student_id' => $student->id,
                    'status' => $computed['skip'] === null ? 'ready' : 'skipped',
                    'skip_reason' => $computed['skip'],
                    'gross_amount' => Money::fromCents($computed['gross']),
                    'discount_amount' => Money::fromCents($computed['discount']),
                    'net_amount' => Money::fromCents($computed['net']),
                    'lines' => array_map(fn (array $l) => ['type' => $l['line_type']->value, 'description' => $l['description'], 'amount' => Money::fromCents($l['amount_cents'])], $computed['lines']),
                    'error_message' => $computed['skip_detail'],
                ]);
            }

            foreach (['gross', 'discount', 'net'] as $key) {
                $totals[$key] = Money::fromCents($totals[$key]);
            }

            $run->forceFill(['totals' => $totals])->save();

            return $run;
        });
    }

    public function submit(BillingRun $run, User $actor): BillingRun
    {
        if ($run->status !== BillingRunStatus::Simulated) {
            throw new ConflictException('Hanya hasil simulasi yang dapat diajukan.');
        }

        if (($run->totals['ready'] ?? 0) === 0) {
            throw new ConflictException('Tidak ada tagihan yang siap diterbitkan pada simulasi ini.');
        }

        $run->forceFill(['status' => BillingRunStatus::PendingApproval, 'submitted_at' => now()])->save();

        return $run;
    }

    /** Persetujuan + posting (SOD-KEU-02). Perhitungan diulang saat posting. */
    public function approve(BillingRun $run, User $actor): BillingRun
    {
        if ($run->status !== BillingRunStatus::PendingApproval) {
            throw new ConflictException('Penerbitan ini tidak sedang menunggu persetujuan.');
        }

        Approvals::assertDistinct($actor, [$run->created_by], 'Pembuat penerbitan tagihan tidak dapat menyetujuinya sendiri (pemisahan tugas).');

        // Klaim atomik: dua penyetuju bersamaan → hanya satu yang memposting.
        $claimed = BillingRun::query()->whereKey($run->id)->where('status', BillingRunStatus::PendingApproval)->whereNull('approved_by')
            ->update(['approved_by' => $actor->id, 'approved_at' => now()]);

        if ($claimed === 0) {
            throw new ConflictException('Penerbitan ini sedang/sudah diproses penyetuju lain.', 'ALREADY_DECIDED');
        }

        $run->refresh();
        $components = FeeComponent::query()->whereIn('id', $run->fee_component_ids)->get()->all();
        $term = $run->academicTerm;
        $posted = ['invoices' => 0, 'skipped' => 0, 'failed' => 0, 'net' => 0];

        foreach ($run->items()->where('status', 'ready')->with('student')->get() as $item) {
            try {
                $invoice = DB::transaction(function () use ($item, $term, $components, $run, $actor) {
                    $computed = $this->compute($item->student, $term, $components, $run->bill_group);

                    if ($computed['skip'] !== null) {
                        $item->forceFill(['status' => 'skipped', 'skip_reason' => $computed['skip'], 'error_message' => $computed['skip_detail']])->save();

                        return null;
                    }

                    $invoice = $this->writer->create($item->student, $term, $run->bill_group, $computed['lines'], $run->due_date, $actor, extra: [
                        'billing_run_id' => $run->id,
                        'rate_snapshot' => $computed['snapshot'],
                    ]);
                    $item->forceFill(['status' => 'posted', 'invoice_id' => $invoice->id, 'net_amount' => $invoice->amount])->save();

                    return $invoice;
                });

                if ($invoice === null) {
                    $posted['skipped']++;
                } else {
                    $posted['invoices']++;
                    $posted['net'] += Money::toCents($invoice->amount);
                }
            } catch (Throwable $e) {
                report($e);
                $item->forceFill(['status' => 'failed', 'error_message' => mb_substr($e->getMessage(), 0, 255)])->save();
                $posted['failed']++;
            }
        }

        $posted['net'] = Money::fromCents($posted['net']);
        $run->forceFill(['status' => BillingRunStatus::Posted, 'posted_at' => now(), 'posted_totals' => $posted])->save();
        $this->audit->recordAction($run, AuditAction::Approved, ['run_number' => $run->run_number, ...$posted]);

        return $run;
    }

    public function reject(BillingRun $run, User $actor, string $reason): BillingRun
    {
        if ($run->status !== BillingRunStatus::PendingApproval) {
            throw new ConflictException('Penerbitan ini tidak sedang menunggu persetujuan.');
        }

        Approvals::assertDistinct($actor, [$run->created_by]);
        $run->forceFill(['status' => BillingRunStatus::Rejected, 'rejection_reason' => $reason, 'approved_by' => $actor->id, 'approved_at' => now()])->save();
        $this->audit->recordAction($run, AuditAction::Rejected, [], $reason);

        return $run;
    }

    public function cancel(BillingRun $run): BillingRun
    {
        if (! in_array($run->status, [BillingRunStatus::Simulated, BillingRunStatus::PendingApproval], true)) {
            throw new ConflictException('Penerbitan yang sudah diproses tidak dapat dibatalkan.');
        }

        $run->forceFill(['status' => BillingRunStatus::Cancelled])->save();

        return $run;
    }

    /**
     * Tagihan biaya per SKS semester (BR-KEU-10/11). Tagihan pertama dibuat
     * baru; perubahan SKS sesudahnya menjadi penyesuaian sistem (bukan
     * mengubah tagihan), pengurangan hanya sebelum batas drop.
     */
    public function billCredits(Student $student, AcademicTerm $term, ?User $actor = null): ?Invoice
    {
        $components = FeeComponent::query()->where('category', FeeCategory::CreditFee)->where('is_active', true)->get();

        if ($components->isEmpty()) {
            return null;
        }

        $computed = $this->compute($student, $term, $components->all(), BillGroup::Credit, ignoreExisting: true);

        if ($computed['skip'] !== null && $computed['skip'] !== self::SKIP_ZERO) {
            return null;
        }

        $existing = Invoice::query()->where('student_id', $student->id)->where('academic_term_id', $term->id)
            ->where('bill_group', BillGroup::Credit)->whereNotIn('status', [InvoiceStatus::Cancelled, InvoiceStatus::Draft])->first();

        if ($existing === null) {
            if ($computed['net'] <= 0) {
                return null;
            }

            return $this->writer->create($student, $term, BillGroup::Credit, $computed['lines'], CarbonImmutable::today()->addDays($this->settings->int('keu.credit_fee.due_days')), $actor, extra: ['rate_snapshot' => $computed['snapshot']]);
        }

        $currentCharges = (int) InvoiceItem::query()->where('invoice_id', $existing->id)->whereIn('line_type', [InvoiceLineType::Charge, InvoiceLineType::Adjustment])
            ->where(fn ($q) => $q->where('source_type', 'fee_rate')->orWhere('source_type', 'credit_adjustment'))
            ->get(['amount'])->sum(fn (InvoiceItem $i) => Money::toCents($i->amount));
        $delta = $computed['gross'] - $currentCharges;

        if ($delta === 0) {
            return $existing;
        }

        if ($delta < 0) {
            $deadline = (string) $this->settings->get('keu.credit_fee.drop_credit_deadline');

            if ($deadline !== '' && CarbonImmutable::today()->greaterThan(CarbonImmutable::parse($deadline))) {
                return $existing;
            }
        }

        $this->adjustments->applySystem($existing, $delta > 0 ? AdjustmentType::Increase : AdjustmentType::Decrease, abs($delta), 'KRS_CHANGE', 'Perubahan SKS terdaftar (KRS).', 'credit_adjustment');

        return $existing->fresh();
    }

    /**
     * @param  array<int, FeeComponent>  $components
     * @return array{lines: array<int, array<string, mixed>>, gross: int, discount: int, net: int, skip: ?string, skip_detail: ?string, snapshot: array<string, mixed>}
     */
    public function compute(Student $student, AcademicTerm $term, array $components, BillGroup $group, bool $ignoreExisting = false): array
    {
        $result = ['lines' => [], 'gross' => 0, 'discount' => 0, 'net' => 0, 'skip' => null, 'skip_detail' => null, 'snapshot' => []];
        $table = $this->tariffs->tableFor($student, $term);

        if ($table === null) {
            return ['skip' => self::SKIP_NO_TABLE, 'skip_detail' => 'Belum ada tabel tarif berlaku.'] + $result;
        }

        $uktGroupId = $this->tariffs->activeUktGroupId($student, $term);
        $level = $this->tariffs->semesterLevel($student, $term);
        $credits = null;
        $charges = [];
        $already = 0;
        $missing = [];

        foreach ($components as $component) {
            $key = "{$student->id}:{$term->id}:{$component->id}:{$group->value}";

            if (! $ignoreExisting && InvoiceItem::query()->where('active_key', $key)->exists()) {
                $already++;

                continue;
            }

            if ($component->category === FeeCategory::Ukt && $uktGroupId === null) {
                return ['skip' => self::SKIP_UKT_MISSING, 'skip_detail' => 'Golongan UKT belum ditetapkan.'] + $result;
            }

            $rate = $this->tariffs->resolve($student, $term, $component, $table, $uktGroupId);

            if ($rate === null) {
                $missing[] = $component->code;

                continue;
            }

            $quantity = 1;
            $unit = Money::toCents($rate->amount);

            if ($rate->calc_basis === CalcBasis::PerCredit) {
                $credits ??= $this->tariffs->creditsFor($student, $term);
                $quantity = $credits;

                if ($rate->min_credits !== null) {
                    $quantity = max($quantity, $rate->min_credits);
                }

                if ($rate->max_credits !== null) {
                    $quantity = min($quantity, $rate->max_credits);
                }
            }

            $cents = $unit * $quantity;

            if ($cents <= 0) {
                continue;
            }

            $charges[] = ['component' => $component, 'cents' => $cents];
            $result['lines'][] = [
                'line_type' => InvoiceLineType::Charge,
                'description' => $component->name.($rate->calc_basis === CalcBasis::PerCredit ? " ({$quantity} SKS)" : ''),
                'amount_cents' => $cents,
                'unit_cents' => $unit,
                'quantity' => $quantity,
                'fee_component_id' => $component->id,
                'source_type' => 'fee_rate',
                'source_id' => $rate->id,
                'active_key' => $ignoreExisting ? null : $key,
            ];
            $result['gross'] += $cents;
            $result['snapshot']['rates'][] = ['component' => $component->code, 'rate_id' => $rate->id, 'amount' => (string) $rate->amount, 'basis' => $rate->calc_basis->value, 'quantity' => $quantity];
        }

        if ($missing !== []) {
            return ['skip' => self::SKIP_NO_RATE, 'skip_detail' => 'Tarif tidak ditemukan: '.implode(', ', $missing)] + $result;
        }

        if ($already > 0 && $charges === []) {
            return ['skip' => self::SKIP_ALREADY_BILLED, 'skip_detail' => 'Sudah ditagihkan.'] + $result;
        }

        if ($result['gross'] <= 0) {
            return ['skip' => self::SKIP_ZERO, 'skip_detail' => 'Nominal tagihan nol.'] + $result;
        }

        foreach ($this->aid->discounts($student, $term, $charges) as $discount) {
            $result['lines'][] = $discount;
            $result['discount'] -= $discount['amount_cents'];
        }

        $result['net'] = $result['gross'] - $result['discount'];
        $result['snapshot'] += [
            'table' => ['id' => $table->id, 'code' => $table->code, 'version' => $table->version],
            'student' => ['study_program_id' => $student->study_program_id, 'admission_year' => $student->admission_year, 'admission_path_code' => $student->admission_path_code, 'study_system_code' => $student->study_system_code, 'ukt_group_id' => $uktGroupId, 'semester_level' => $level],
        ];

        return $result;
    }

    /**
     * @param  array<string, mixed>  $scope
     * @param  Collection<int, FeeComponent>  $components
     * @return LazyCollection<int, Student>
     */
    private function eligibleStudents(array $scope, $components)
    {
        $statuses = (array) $this->settings->get('keu.billing.eligible_statuses');

        if ($components->every(fn (FeeComponent $c) => $c->category === FeeCategory::Leave)) {
            $statuses = [StudentStatus::Leave->value];
        }

        return Student::query()
            ->whereIn('status', $statuses)
            ->when(! empty($scope['study_program_ids']), fn ($q) => $q->whereIn('study_program_id', $scope['study_program_ids']))
            ->when(! empty($scope['admission_years']), fn ($q) => $q->whereIn('admission_year', $scope['admission_years']))
            ->when(! empty($scope['student_ids']), fn ($q) => $q->whereIn('id', $scope['student_ids']))
            ->orderBy('nim')
            ->lazy(500);
    }
}
