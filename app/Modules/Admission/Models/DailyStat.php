<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;

/**
 * @property string $id
 * @property string $university_id
 * @property CarbonImmutable $stat_date
 * @property string $period_id
 * @property string $wave_id
 * @property string $admission_path_id
 * @property string $study_program_id
 * @property string $metric
 * @property string $value
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class DailyStat extends PmbModel
{
    protected $table = 'pmb_daily_stats';

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'value' => 'decimal:2',
        ];
    }
}
