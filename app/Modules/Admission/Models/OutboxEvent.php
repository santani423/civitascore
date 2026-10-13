<?php

namespace Modules\Admission\Models;

use Modules\Admission\Enums\OutboxStatus;

class OutboxEvent extends PmbModel
{
    protected $table = 'pmb_outbox_events';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => OutboxStatus::class,
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'dispatched_at' => 'datetime',
        ];
    }
}
