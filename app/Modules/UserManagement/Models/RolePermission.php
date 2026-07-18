<?php

namespace Modules\UserManagement\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Cache;

/**
 * @property string $id
 * @property string $role_id
 * @property string $permission_id
 * @property string|null $granted_by
 * @property CarbonImmutable $created_at
 * @property-read User|null $grantedBy
 * @property-read Permission $permission
 * @property-read Role $role
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermission whereGrantedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermission wherePermissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermission whereRoleId($value)
 *
 * @mixin \Eloquent
 */
class RolePermission extends Pivot
{
    // Pivot (which is just Model + AsPivot + $incrementing=false) rather
    // than plain Model, so Larastan's BelongsToMany<..., TPivotModel of
    // Pivot> generic bound is satisfiable — Role::permissions()'s ->using()
    // needs a Pivot subclass. AsPivot itself is inherited from Pivot, not
    // re-used here.
    use HasUlids;

    // Without an explicit $table, AsPivot::getTable() derives one from the
    // class name (singular: "role_permission") instead of Eloquent's normal
    // pluralized convention — only matters for direct RolePermission::query()
    // calls, since attach()/sync() always pass the real table name explicitly.
    protected $table = 'role_permissions';

    const UPDATED_AT = null;

    protected $fillable = ['role_id', 'permission_id', 'granted_by'];

    // AsPivot::getCreatedAtColumn()/getUpdatedAtColumn() ignore this class's
    // own UPDATED_AT constant entirely — they delegate to the relation's
    // *parent* model (Role, which has a normal updated_at) instead. Without
    // these two overrides, Eloquent tries to write a nonexistent
    // role_permissions.updated_at column on every attach()/sync().
    public function getCreatedAtColumn(): ?string
    {
        return 'created_at';
    }

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<Permission, $this>
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    protected static function booted(): void
    {
        static::created(fn (): mixed => Cache::tags(['permissions'])->flush());
        static::deleted(fn (): mixed => Cache::tags(['permissions'])->flush());
    }
}
