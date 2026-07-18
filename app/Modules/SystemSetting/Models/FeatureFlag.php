<?php

namespace Modules\SystemSetting\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Support\Auditable;
use Modules\SystemSetting\Database\Factories\FeatureFlagFactory;

/**
 * @property string $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property bool $is_enabled
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $updatedBy
 *
 * @method static \Modules\SystemSetting\Database\Factories\FeatureFlagFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureFlag whereUpdatedBy($value)
 *
 * @mixin \Eloquent
 */
class FeatureFlag extends Model
{
    /** @use HasFactory<FeatureFlagFactory> */
    use Auditable, HasFactory, HasUlids;

    protected $fillable = ['key', 'name', 'description', 'is_enabled', 'updated_by'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function newFactory(): FeatureFlagFactory
    {
        return FeatureFlagFactory::new();
    }
}
