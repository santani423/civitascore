<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversityModule extends Model
{
    use HasUlids;

    protected $fillable = [
        'university_id', 'module_id', 'is_enabled', 'enabled_at', 'disabled_at', 'configuration', 'usage_limit',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'enabled_at' => 'datetime',
            'disabled_at' => 'datetime',
            'configuration' => 'array',
        ];
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(PlatformModule::class, 'module_id');
    }
}
