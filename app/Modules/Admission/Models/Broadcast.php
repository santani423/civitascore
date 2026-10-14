<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Modules\Admission\Enums\BroadcastStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $title
 * @property string $channel
 * @property array<mixed> $audience_filter
 * @property string|null $content_subject
 * @property string $content_body
 * @property BroadcastStatus $status
 * @property CarbonImmutable|null $scheduled_at
 * @property int $recipients_count
 * @property int $sent_count
 * @property int $failed_count
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
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
