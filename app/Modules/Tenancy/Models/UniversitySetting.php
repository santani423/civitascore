<?php

namespace Modules\Tenancy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SystemSetting\Enums\SettingValueType;

class UniversitySetting extends Model
{
    use HasUlids;

    protected $fillable = ['university_id', 'key', 'value', 'type', 'group', 'description', 'is_public', 'updated_by'];

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

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
