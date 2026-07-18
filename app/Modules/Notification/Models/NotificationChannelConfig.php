<?php

namespace Modules\Notification\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Notification\Database\Factories\NotificationChannelConfigFactory;
use Modules\Notification\Enums\NotificationChannel;

/**
 * Named *ChannelConfig* (not *Channel*) to avoid a name collision with the
 * Modules\Notification\Enums\NotificationChannel enum this model casts to.
 *
 * @property string $id
 * @property NotificationChannel $code
 * @property string $name
 * @property bool $is_enabled
 * @property array<array-key, mixed>|null $config
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static \Modules\Notification\Database\Factories\NotificationChannelConfigFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationChannelConfig whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class NotificationChannelConfig extends Model
{
    /** @use HasFactory<NotificationChannelConfigFactory> */
    use HasFactory, HasUlids;

    protected $table = 'notification_channels';

    protected $fillable = ['code', 'name', 'is_enabled', 'config'];

    protected function casts(): array
    {
        return [
            'code' => NotificationChannel::class,
            'is_enabled' => 'boolean',
            'config' => 'array',
        ];
    }

    protected static function newFactory(): NotificationChannelConfigFactory
    {
        return NotificationChannelConfigFactory::new();
    }
}
