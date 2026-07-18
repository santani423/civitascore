<?php

namespace Modules\UserManagement\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\AuditLog\Support\Auditable;
use Modules\UserManagement\Database\Factories\RoleFactory;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_system
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RolePermission|null $pivot
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 *
 * @method static \Modules\UserManagement\Database\Factories\RoleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use Auditable, HasFactory, HasUlids;

    protected $fillable = ['name', 'slug', 'description', 'is_system'];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Permission, $this, RolePermission>
     */
    public function permissions(): BelongsToMany
    {
        // ->using() is required, not cosmetic: without it, attach()/sync()
        // insert pivot rows via a raw query builder statement that never
        // instantiates RolePermission, so its ULID (and the cache-flush
        // hook) never fires and the insert fails a NOT NULL constraint.
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->using(RolePermission::class)
            ->withPivot(['granted_by', 'created_at']);
    }

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }
}
