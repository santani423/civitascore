<?php

namespace Modules\Notification\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Notification\Database\Factories\NotificationTemplateFactory;
use Modules\Notification\Enums\NotificationChannel;

/**
 * @property string $id
 * @property string $event_key
 * @property string $name
 * @property NotificationChannel $channel
 * @property string|null $subject
 * @property string $body_template
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static \Modules\Notification\Database\Factories\NotificationTemplateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereBodyTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereEventKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class NotificationTemplate extends Model
{
    /** @use HasFactory<NotificationTemplateFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['event_key', 'name', 'channel', 'subject', 'body_template', 'is_active'];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): NotificationTemplateFactory
    {
        return NotificationTemplateFactory::new();
    }
}
