<?php

namespace Modules\UserManagement\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\AuditLog\Support\Auditable;
use Modules\UserManagement\Database\Factories\PermissionFactory;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property PermissionScope $scope
 * @property PermissionAction $action
 * @property string|null $resource
 * @property string|null $description
 * @property bool $is_system
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 *
 * @method static \Modules\UserManagement\Database\Factories\PermissionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereResource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use Auditable, HasFactory, HasUlids;

    protected $fillable = ['name', 'slug', 'scope', 'action', 'resource', 'description', 'is_system'];

    protected function casts(): array
    {
        return [
            'scope' => PermissionScope::class,
            'action' => PermissionAction::class,
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this, RolePermission>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')->using(RolePermission::class);
    }

    public static function slugFor(string $resource, PermissionAction $action): string
    {
        return "{$resource}.{$action->value}";
    }

    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }
}
