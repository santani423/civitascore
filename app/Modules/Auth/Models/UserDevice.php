<?php

namespace Modules\Auth\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Database\Factories\UserDeviceFactory;
use Modules\Auth\Enums\DeviceType;

/**
 * @property string $id
 * @property string $user_id
 * @property string $device_identifier
 * @property string|null $device_name
 * @property DeviceType $device_type
 * @property string|null $platform
 * @property string|null $push_token
 * @property bool $is_trusted
 * @property CarbonImmutable|null $last_used_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User|null $user
 *
 * @method static \Modules\Auth\Database\Factories\UserDeviceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereIsTrusted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice wherePushToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice withoutTrashed()
 *
 * @mixin \Eloquent
 */
class UserDevice extends Model
{
    /** @use HasFactory<UserDeviceFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id', 'device_identifier', 'device_name', 'device_type',
        'platform', 'push_token', 'is_trusted', 'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'device_type' => DeviceType::class,
            'is_trusted' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): UserDeviceFactory
    {
        return UserDeviceFactory::new();
    }
}
