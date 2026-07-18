<?php

namespace Modules\SystemSetting\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Support\Auditable;
use Modules\SystemSetting\Database\Factories\SystemSettingFactory;
use Modules\SystemSetting\Enums\SettingValueType;

/**
 * @property string $id
 * @property string $key
 * @property string|null $value
 * @property SettingValueType $type
 * @property string|null $group
 * @property string|null $description
 * @property bool $is_public
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $updatedBy
 *
 * @method static \Modules\SystemSetting\Database\Factories\SystemSettingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereValue($value)
 *
 * @mixin \Eloquent
 */
class SystemSetting extends Model
{
    /** @use HasFactory<SystemSettingFactory> */
    use Auditable, HasFactory, HasUlids;

    protected $fillable = ['key', 'value', 'type', 'group', 'description', 'is_public', 'updated_by'];

    protected function casts(): array
    {
        return [
            'type' => SettingValueType::class,
            'is_public' => 'boolean',
        ];
    }

    public function castedValue(): mixed
    {
        return $this->type->cast($this->value);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function newFactory(): SystemSettingFactory
    {
        return SystemSettingFactory::new();
    }
}
