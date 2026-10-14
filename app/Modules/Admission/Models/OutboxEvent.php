<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Modules\Admission\Enums\OutboxStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $event_type
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property array<mixed> $payload
 * @property OutboxStatus $status
 * @property int $attempts
 * @property CarbonImmutable $available_at
 * @property CarbonImmutable|null $dispatched_at
 * @property string|null $last_error
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
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
