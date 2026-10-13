<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\WaveStatus;

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
