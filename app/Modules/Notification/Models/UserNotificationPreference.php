<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notification\Database\Factories\UserNotificationPreferenceFactory;
use Modules\Notification\Enums\NotificationChannel;

/**
 * @property string $id
 * @property string $user_id
 * @property NotificationChannel $channel
 * @property string $notification_type
 * @property bool $is_enabled
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 *
 * @method static \Modules\Notification\Database\Factories\UserNotificationPreferenceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereNotificationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserNotificationPreference extends Model
{
    /** @use HasFactory<UserNotificationPreferenceFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['user_id', 'channel', 'notification_type', 'is_enabled'];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): UserNotificationPreferenceFactory
    {
        return UserNotificationPreferenceFactory::new();
    }
}
