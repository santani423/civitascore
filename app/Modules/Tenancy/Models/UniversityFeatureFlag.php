<?php

namespace Modules\Tenancy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Support\Auditable;
use Modules\SystemSetting\Services\FeatureFlagService;

/**
 * Override per universitas atas flag global feature_flags dengan key yang
 * sama — dibaca FeatureFlagService::isEnabled().
 *
 * @property string $id
 * @property string $university_id
 * @property string $key
 * @property bool $is_enabled
 * @property string|null $updated_by
 */
class UniversityFeatureFlag extends Model
{
    use Auditable, HasUlids;

    protected $fillable = ['university_id', 'key', 'is_enabled', 'updated_by'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Juga untuk penulisan di luar FeatureFlagService (mis. UniversitySeeder).
        $forget = fn (self $override) => FeatureFlagService::forgetOverride($override->key, $override->university_id);

        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
