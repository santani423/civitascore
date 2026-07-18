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
 * @property string $user_id
 * @property string $role_id
 * @property string|null $scope_type
 * @property string|null $scope_id
 * @property string|null $assigned_by
 * @property CarbonImmutable $assigned_at
 * @property CarbonImmutable|null $expires_at
 * @property-read User|null $assignedBy
 * @property-read Role $role
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereAssignedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereScopeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereScopeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserRole extends Pivot
{
    // Pivot (not plain Model) so Larastan's BelongsToMany<..., TPivotModel
    // of Pivot> generic bound is satisfiable — see RolePermission for the
    // full explanation.
    use HasUlids;

    // Without an explicit $table, AsPivot::getTable() derives one from the
    // class name (singular: "user_role") instead of Eloquent's normal
    // pluralized convention — only matters for direct UserRole::query()
    // calls (AssignRoleAction, PermissionRegistry, ...), since attach()/
    // sync() always pass the real table name explicitly regardless.
    protected $table = 'user_roles';

    public $timestamps = false;

    // scope_type/scope_id: optional polymorphic scope this grant is
    // restricted to (e.g. later a specific Faculty), null = global grant.
    // No target table exists yet in Phase 1, so this stays a loose
    // reference (no FK) until an Institution-style module defines one.
    protected $fillable = ['user_id', 'role_id', 'scope_type', 'scope_id', 'assigned_by', 'assigned_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // AsPivot::getCreatedAtColumn()/getUpdatedAtColumn() would otherwise
    // delegate to the relation's parent model (User) — see RolePermission
    // for the full explanation. This table uses assigned_at/expires_at
    // instead of created_at/updated_at entirely.
    public function getCreatedAtColumn(): ?string
    {
        return null;
    }

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    protected static function booted(): void
    {
        static::created(fn (): mixed => Cache::tags(['permissions'])->flush());
        static::deleted(fn (): mixed => Cache::tags(['permissions'])->flush());
    }
}
