<?php

use App\Models\User;
use Modules\UserManagement\Models\Role;

test('assigning a role to a user succeeds', function () {
    actingAsUserWithPermissions(['user_roles.create']);

    $targetUser = User::factory()->create();
    $role = Role::factory()->create();

    $response = $this->postJson("/api/v1/users/{$targetUser->id}/roles", ['role_id' => $role->id]);

    $response->assertApiSuccess(201);
    expect($targetUser->roles()->where('role_id', $role->id)->exists())->toBeTrue();
});

test('assigning the same role twice is a conflict', function () {
    actingAsUserWithPermissions(['user_roles.create']);

    $targetUser = User::factory()->create();
    $role = Role::factory()->create();

    $this->postJson("/api/v1/users/{$targetUser->id}/roles", ['role_id' => $role->id])->assertApiSuccess(201);

    $response = $this->postJson("/api/v1/users/{$targetUser->id}/roles", ['role_id' => $role->id]);

    $response->assertApiError(409);
});

test('revoking a role from a user succeeds', function () {
    actingAsUserWithPermissions(['user_roles.create', 'user_roles.delete']);

    $targetUser = User::factory()->create();
    $role = Role::factory()->create();
    $targetUser->roles()->attach($role->id, ['assigned_at' => now()]);

    $response = $this->deleteJson("/api/v1/users/{$targetUser->id}/roles/{$role->id}");

    $response->assertApiSuccess();
    expect($targetUser->roles()->where('role_id', $role->id)->exists())->toBeFalse();
});
