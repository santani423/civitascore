<?php

use App\Models\User;
use Modules\UserManagement\Actions\RevokeRoleAction;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

test('a user without the required permission is forbidden', function () {
    actingAsUserWithPermissions([]);

    $response = $this->getJson('/api/v1/roles');

    $response->assertApiError(403);
});

test('a user with the required permission is allowed', function () {
    actingAsUserWithPermissions(['roles.read']);

    $response = $this->getJson('/api/v1/roles');

    $response->assertApiSuccess();
});

test('a super_admin bypasses every permission check', function () {
    $user = User::factory()->create();
    $superAdminRole = Role::factory()->create(['slug' => 'super_admin']);
    $user->roles()->attach($superAdminRole->id, ['assigned_at' => now()]);
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/roles');

    $response->assertApiSuccess();
});

test('revoking a role immediately invalidates the cached permission set', function () {
    $user = actingAsUserWithPermissions(['roles.read']);

    $this->getJson('/api/v1/roles')->assertApiSuccess();

    $role = UserRole::query()->where('user_id', $user->id)->first()->role;
    app(RevokeRoleAction::class)->execute($user, $role);

    $response = $this->getJson('/api/v1/roles');

    $response->assertApiError(403);
});
