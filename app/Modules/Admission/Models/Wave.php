<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\WaveStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $period_id
 * @property string $code
 * @property string $name
 * @property int $sequence
 * @property CarbonImmutable $registration_opens_at
 * @property CarbonImmutable $registration_closes_at
 * @property CarbonImmutable $payment_deadline_at
 * @property CarbonImmutable $form_lock_at
 * @property CarbonImmutable|null $exam_starts_on
 * @property CarbonImmutable|null $exam_ends_on
 * @property CarbonImmutable $announcement_at
 * @property CarbonImmutable $re_registration_opens_at
 * @property CarbonImmutable $re_registration_closes_at
 * @property bool $is_ods
 * @property WaveStatus $status
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Period $period
 * @property-read Collection<int, WavePath> $wavePaths
 */
class Wave extends PmbModel
{
    protected $table = 'pmb_waves';

    protected function casts(): array
    {
        return [
            'status' => WaveStatus::class,
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'payment_deadline_at' => 'datetime',
            'form_lock_at' => 'datetime',
            'exam_starts_on' => 'date',
            'exam_ends_on' => 'date',
            'announcement_at' => 'datetime',
            're_registration_opens_at' => 'datetime',
            're_registration_closes_at' => 'datetime',
            'is_ods' => 'boolean',
            'sequence' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Period, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    /**
     * @return HasMany<WavePath, $this>
     */
    public function wavePaths(): HasMany
    {
        return $this->hasMany(WavePath::class, 'wave_id');
    }
}
