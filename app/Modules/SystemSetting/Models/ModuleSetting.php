<?php

namespace Modules\SystemSetting\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\SystemSetting\Database\Factories\ModuleSettingFactory;
use Modules\SystemSetting\Enums\SettingValueType;

/**
 * @property string $id
 * @property string $module_key
 * @property string $key
 * @property string|null $value
 * @property SettingValueType $type
 * @property bool $is_active
 * @property bool $is_visible
 * @property bool $is_editable
 * @property CarbonImmutable|null $available_from
 * @property CarbonImmutable|null $available_until
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static \Modules\SystemSetting\Database\Factories\ModuleSettingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereAvailableFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereAvailableUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereIsEditable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereIsVisible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereModuleKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleSetting whereValue($value)
 *
 * @mixin \Eloquent
 */
class ModuleSetting extends Model
{
    /** @use HasFactory<ModuleSettingFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'module_key', 'key', 'value', 'type', 'is_active', 'is_visible',
        'is_editable', 'available_from', 'available_until',
    ];

    protected function casts(): array
    {
        return [
            'type' => SettingValueType::class,
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'is_editable' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    public function castedValue(): mixed
    {
        return $this->type->cast($this->value);
    }

    public function isCurrentlyAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    protected static function newFactory(): ModuleSettingFactory
    {
        return ModuleSettingFactory::new();
    }
}
