<?php

use Modules\UserManagement\Models\Role;

test('a permitted user can create, update, and delete a role', function () {
    actingAsUserWithPermissions(['roles.create', 'roles.read', 'roles.update', 'roles.delete']);

    $store = $this->postJson('/api/v1/roles', ['name' => 'Dosen Pembimbing']);
    $store->assertApiSuccess(201);
    $roleId = $store->json('data.id');

    expect(Role::query()->find($roleId)->slug)->toBe('dosen_pembimbing');

    $update = $this->putJson("/api/v1/roles/{$roleId}", ['name' => 'Dosen Pembimbing Akademik']);
    $update->assertApiSuccess();
    expect($update->json('data.name'))->toBe('Dosen Pembimbing Akademik');

    $destroy = $this->deleteJson("/api/v1/roles/{$roleId}");
    $destroy->assertApiSuccess();
    expect(Role::query()->find($roleId))->toBeNull();
});

test('creating a role without a name fails validation', function () {
    actingAsUserWithPermissions(['roles.create']);

    $response = $this->postJson('/api/v1/roles', []);

    $response->assertApiError(422);
});

test('a system role cannot be deleted', function () {
    actingAsUserWithPermissions(['roles.delete']);

    $role = Role::factory()->create(['is_system' => true]);

    $response = $this->deleteJson("/api/v1/roles/{$role->id}");

    $response->assertApiError(409);
    expect(Role::query()->find($role->id))->not->toBeNull();
});
