<?php

namespace Modules\Admission\Models;

use Modules\Admission\Enums\BroadcastStatus;

class Broadcast extends PmbModel
{
    protected $table = 'pmb_broadcasts';

    protected function casts(): array
    {
        return [
            'audience_filter' => 'array',
            'status' => BroadcastStatus::class,
            'scheduled_at' => 'datetime',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }
}
