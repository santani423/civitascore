<?php

namespace Modules\Auth\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Database\Factories\LoginHistoryFactory;
use Modules\Auth\Enums\LoginFailureReason;
use Modules\Auth\Enums\LoginHistoryStatus;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string|null $email_attempted
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $user_device_id
 * @property LoginHistoryStatus $status
 * @property LoginFailureReason|null $failure_reason
 * @property CarbonImmutable $created_at
 * @property-read UserDevice|null $device
 * @property-read User|null $user
 *
 * @method static \Modules\Auth\Database\Factories\LoginHistoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory whereEmailAttempted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory whereFailureReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory whereUserDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginHistory whereUserId($value)
 *
 * @mixin \Eloquent
 */
class LoginHistory extends Model
{
    /** @use HasFactory<LoginHistoryFactory> */
    use HasFactory, HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'email_attempted', 'ip_address', 'user_agent',
        'user_device_id', 'status', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoginHistoryStatus::class,
            'failure_reason' => LoginFailureReason::class,
            'created_at' => 'datetime',
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

    protected static function newFactory(): LoginHistoryFactory
    {
        return LoginHistoryFactory::new();
    }
}
