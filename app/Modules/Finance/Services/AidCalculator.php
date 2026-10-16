<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\AidAwardStatus;
use Modules\Finance\Enums\AidCalcType;
use Modules\Finance\Enums\AidKind;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Models\FeeComponent;
use Modules\Finance\Models\FinancialAidAward;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;

/**
 * Penerapan beasiswa/subsidi/potongan saat tagihan dibuat (§8.3.3,
 * BR-KEU-21..24). Urutan = prioritas kebijakan; mode `sequential` menghitung
 * atas sisa, `parallel` atas bruto. Potongan per komponen tidak pernah
 * melebihi sisa komponen. Award non-stackable: hanya yang terbesar.
 */
class AidCalculator
{
    public function __construct(private readonly KeuSettings $settings) {}

    /**
     * @param  array<int, array{component: FeeComponent, cents: int}>  $charges
     * @return array<int, array{line_type: InvoiceLineType, description: string, amount_cents: int, fee_component_id: string, source_type: string, source_id: string}>
     */
    public function discounts(Student $student, AcademicTerm $term, array $charges): array
    {
        $awards = $this->activeAwards($student, $term);

        if ($awards->isEmpty() || $charges === []) {
            return [];
        }

        $nonStackable = $awards->filter(fn (FinancialAidAward $a) => ! $a->policy->stackable);

        if ($nonStackable->count() > 1) {
            // amount_cents bertanda negatif: potongan terbesar = jumlah paling kecil.
            $best = $nonStackable->sortBy(fn (FinancialAidAward $a) => array_sum(array_column($this->compute(collect([$a]), $charges), 'amount_cents')))->first();
            $awards = $awards->reject(fn (FinancialAidAward $a) => ! $a->policy->stackable && $a->id !== $best->id)->values();
        }

        return $this->compute($awards, $charges);
    }

    /**
     * Potongan satu award atas baris biaya tertentu (penerapan retroaktif).
     *
     * @param  array<int, array{component: FeeComponent, cents: int}>  $charges
     * @return array<int, array<string, mixed>>
     */
    public function previewAward(FinancialAidAward $award, array $charges): array
    {
        $award->loadMissing('policy');

        return $this->compute(collect([$award]), $charges);
    }

    /** @return Collection<int, FinancialAidAward> */
    public function activeAwards(Student $student, AcademicTerm $term): Collection
    {
        return FinancialAidAward::query()
            ->where('student_id', $student->id)
            ->where('status', AidAwardStatus::Active)
            ->with(['policy', 'startTerm', 'endTerm'])
            ->get()
            ->filter(fn (FinancialAidAward $a) => ($a->startTerm === null || $a->startTerm->start_date <= $term->start_date)
                && ($a->endTerm === null || $a->endTerm->start_date >= $term->start_date)
                && $a->policy !== null && $a->policy->is_active)
            ->sortBy(fn (FinancialAidAward $a) => [$a->policy->priority, $a->created_at])
            ->values();
    }

    /**
     * @param  Collection<int, FinancialAidAward>  $awards
     * @param  array<int, array{component: FeeComponent, cents: int}>  $charges
     * @return array<int, array<string, mixed>>
     */
    private function compute(Collection $awards, array $charges): array
    {
        $sequential = $this->settings->get('keu.discount.stacking_mode') !== 'parallel';
        $unit = max(1, $this->settings->int('keu.rounding.unit')) * 100;
        $lines = [];

        foreach ($charges as $charge) {
            $component = $charge['component'];
            $gross = $charge['cents'];
            $remaining = $gross;

            foreach ($awards as $award) {
                $componentIds = $award->fee_component_ids ?? $award->policy->fee_component_ids;
                $explicit = is_array($componentIds) && in_array($component->id, $componentIds, true);

                if ((is_array($componentIds) && ! $explicit) || (! $component->discountable && ! $explicit) || $remaining <= 0) {
                    continue;
                }

                $base = $sequential ? $remaining : $gross;
                $cents = match ($award->calc_type) {
                    AidCalcType::Full => $remaining,
                    AidCalcType::Percent => Money::percentCents($base, (float) $award->value, $unit),
                    AidCalcType::Amount => Money::toCents($award->value),
                };

                if ($award->max_amount !== null) {
                    $cents = min($cents, Money::toCents($award->max_amount));
                }

                $cents = min($cents, $remaining);

                if ($cents <= 0) {
                    continue;
                }

                $remaining -= $cents;
                $lines[] = [
                    'line_type' => match ($award->policy->kind) {
                        AidKind::Discount => InvoiceLineType::Discount,
                        AidKind::Subsidy => InvoiceLineType::Subsidy,
                        default => InvoiceLineType::Aid,
                    },
                    'description' => $award->policy->name.' — '.$component->name,
                    'amount_cents' => -$cents,
                    'fee_component_id' => $component->id,
                    'source_type' => 'financial_aid_award',
                    'source_id' => $award->id,
                ];
            }
        }

        return $lines;
    }
}
