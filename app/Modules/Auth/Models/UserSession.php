<?php

namespace Modules\Auth\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Database\Factories\UserSessionFactory;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $user_device_id
 * @property string|null $login_history_id
 * @property string $session_token
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonImmutable|null $last_activity_at
 * @property CarbonImmutable|null $revoked_at
 * @property string|null $revoked_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read UserDevice|null $device
 * @property-read LoginHistory|null $loginHistory
 * @property-read User|null $user
 *
 * @method static \Modules\Auth\Database\Factories\UserSessionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereLoginHistoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereRevokedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereRevokedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereSessionToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereUserDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSession whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserSession extends Model
{
    /** @use HasFactory<UserSessionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id', 'user_device_id', 'login_history_id', 'session_token',
        'ip_address', 'user_agent', 'last_activity_at', 'revoked_at', 'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<UserDevice, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'user_device_id');
    }

    /**
     * @return BelongsTo<LoginHistory, $this>
     */
    public function loginHistory(): BelongsTo
    {
        return $this->belongsTo(LoginHistory::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    protected static function newFactory(): UserSessionFactory
    {
        return UserSessionFactory::new();
    }
}
