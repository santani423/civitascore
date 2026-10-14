<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\Recommendation;

/**
 * @property string $id
 * @property string $university_id
 * @property string $selection_run_id
 * @property string $application_id
 * @property bool $eligible
 * @property string|null $ineligible_reason
 * @property array<mixed> $component_scores
 * @property string|null $final_score
 * @property string|null $final_score_rounded
 * @property int|null $rank_global
 * @property Recommendation $recommendation
 * @property string|null $fail_reason
 * @property string|null $assigned_choice_id
 * @property string|null $assigned_quota_id
 * @property array<mixed>|null $waitlist_entries
 * @property array<mixed>|null $tie_break_values
 * @property bool $tie_unresolved
 * @property string|null $override_recommendation
 * @property string|null $override_quota_id
 * @property string|null $override_reason
 * @property string|null $override_by
 * @property CarbonImmutable|null $created_at
 * @property-read SelectionRun $run
 * @property-read Application $application
 * @property-read Quota|null $assignedQuota
 * @property-read ApplicationChoice|null $assignedChoice
 */
class SelectionRunResult extends PmbModel
{
    protected $table = 'pmb_selection_run_results';

    public const UPDATED_AT = null;

    /** Kolom yang boleh diubah setelah insert (override saat `under_review`, H.7). */
    public const MUTABLE = ['override_recommendation', 'override_quota_id', 'override_reason', 'override_by'];

    protected static function booted(): void
    {
        // Hasil hitung insert-only (06 §H.7): hanya lapisan override yang dapat diubah.
        static::updating(function (self $result): void {
            $illegal = array_diff(array_keys($result->getDirty()), self::MUTABLE);

            if ($illegal !== []) {
                throw new \LogicException('pmb_selection_run_results bersifat insert-only: '.implode(', ', $illegal));
            }
        });

        static::deleting(function (): void {
            throw new \LogicException('pmb_selection_run_results tidak dapat dihapus.');
        });
    }

    public function effectiveRecommendation(): string
    {
        return $this->override_recommendation ?? $this->recommendation->value;
    }

    public function effectiveQuotaId(): ?string
    {
        return $this->override_recommendation !== null ? $this->override_quota_id : $this->assigned_quota_id;
    }

    protected function casts(): array
    {
        return [
            'eligible' => 'boolean',
            'component_scores' => 'array',
            'final_score' => 'decimal:4',
            'final_score_rounded' => 'decimal:2',
            'rank_global' => 'integer',
            'recommendation' => Recommendation::class,
            'waitlist_entries' => 'array',
            'tie_break_values' => 'array',
            'tie_unresolved' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SelectionRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(SelectionRun::class, 'selection_run_id');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return BelongsTo<Quota, $this>
     */
    public function assignedQuota(): BelongsTo
    {
        return $this->belongsTo(Quota::class, 'assigned_quota_id');
    }

    /**
     * @return BelongsTo<ApplicationChoice, $this>
     */
    public function assignedChoice(): BelongsTo
    {
        return $this->belongsTo(ApplicationChoice::class, 'assigned_choice_id');
    }
}
