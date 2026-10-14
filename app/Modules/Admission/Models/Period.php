<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\AcademicTerm;
use Modules\Admission\Enums\PeriodStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $entry_academic_term_id
 * @property string $code
 * @property string $name
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property PeriodStatus $status
 * @property string|null $description
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $closed_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AcademicTerm $entryTerm
 * @property-read Collection<int, Wave> $waves
 * @property-read Collection<int, Quota> $quotas
 * @property-read Collection<int, FeeComponent> $feeComponents
 */
class Period extends PmbModel
{
    protected $table = 'pmb_periods';

    protected function casts(): array
    {
        return [
            'status' => PeriodStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function entryTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'entry_academic_term_id');
    }

    /**
     * @return HasMany<Wave, $this>
     */
    public function waves(): HasMany
    {
        return $this->hasMany(Wave::class, 'period_id');
    }

    /**
     * @return HasMany<Quota, $this>
     */
    public function quotas(): HasMany
    {
        return $this->hasMany(Quota::class, 'period_id');
    }

    /**
     * @return HasMany<FeeComponent, $this>
     */
    public function feeComponents(): HasMany
    {
        return $this->hasMany(FeeComponent::class, 'period_id');
    }
}
