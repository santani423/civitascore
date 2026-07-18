<?php

namespace Modules\Notification\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Notification\Database\Factories\NotificationLogFactory;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Enums\NotificationLogStatus;

/**
 * @property string $id
 * @property string|null $notification_id
 * @property string $notifiable_type
 * @property string $notifiable_id
 * @property NotificationChannel $channel
 * @property string $event_key
 * @property NotificationLogStatus $status
 * @property string|null $error_message
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Model|\Eloquent $notifiable
 *
 * @method static \Modules\Notification\Database\Factories\NotificationLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereEventKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereNotifiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereNotifiableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereNotificationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class NotificationLog extends Model
{
    /** @use HasFactory<NotificationLogFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'notification_id', 'notifiable_type', 'notifiable_id', 'channel',
        'event_key', 'status', 'error_message', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationLogStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): NotificationLogFactory
    {
        return NotificationLogFactory::new();
    }
}
