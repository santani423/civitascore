<?php

namespace Modules\Admission\Models;

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
