<?php

use App\Models\User;
use Modules\UserManagement\Actions\AssignRoleAction;
use Modules\UserManagement\Actions\RevokeRoleAction;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Support\PermissionRegistry;

test('an expired role grant is excluded from the effective permission set', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $permission = Permission::factory()->create(['slug' => 'demo.read']);
    $role->permissions()->sync([$permission->id => ['created_at' => now()]]);

    $user->roles()->attach($role->id, ['assigned_at' => now(), 'expires_at' => now()->subDay()]);

    $registry = app(PermissionRegistry::class);

    expect($registry->userHasPermission($user, 'demo.read'))->toBeFalse();
    expect($registry->userHasRole($user, $role->slug))->toBeFalse();
});

test('an active, non-expired role grant is included in the effective permission set', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $permission = Permission::factory()->create(['slug' => 'demo.write']);
    $role->permissions()->sync([$permission->id => ['created_at' => now()]]);

    $user->roles()->attach($role->id, ['assigned_at' => now(), 'expires_at' => null]);

    $registry = app(PermissionRegistry::class);

    expect($registry->userHasPermission($user, 'demo.write'))->toBeTrue();
});

test('assigning a role via AssignRoleAction invalidates the cached permission set for that user', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $permission = Permission::factory()->create(['slug' => 'demo.export']);
    $role->permissions()->sync([$permission->id => ['created_at' => now()]]);

    $registry = app(PermissionRegistry::class);

    // Warm the cache with a "false" result before the role is assigned.
    expect($registry->userHasPermission($user, 'demo.export'))->toBeFalse();

    app(AssignRoleAction::class)->execute($user, $role);

    expect($registry->userHasPermission($user, 'demo.export'))->toBeTrue();
});

test('a role scoped to one record does not satisfy a check against a different record', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();

    app(AssignRoleAction::class)->execute($user, $role, scopeType: 'faculty', scopeId: 'faculty-a');

    $registry = app(PermissionRegistry::class);

    expect($registry->userHasRoleInScope($user, $role->slug, 'faculty', 'faculty-a'))->toBeTrue();
    expect($registry->userHasRoleInScope($user, $role->slug, 'faculty', 'faculty-b'))->toBeFalse();
    expect($registry->userHasRole($user, $role->slug))->toBeTrue();
});

test('a global (unscoped) role grant satisfies any scoped check for that role', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();

    app(AssignRoleAction::class)->execute($user, $role);

    $registry = app(PermissionRegistry::class);

    expect($registry->userHasRoleInScope($user, $role->slug, 'faculty', 'faculty-a'))->toBeTrue();
    expect($registry->userHasRoleInScope($user, $role->slug, null, null))->toBeTrue();
});

test('the same role can be granted to a user twice under different scopes simultaneously', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();

    app(AssignRoleAction::class)->execute($user, $role, scopeType: 'faculty', scopeId: 'faculty-a');
    app(AssignRoleAction::class)->execute($user, $role, scopeType: 'faculty', scopeId: 'faculty-b');

    $registry = app(PermissionRegistry::class);

    expect($registry->userHasRoleInScope($user, $role->slug, 'faculty', 'faculty-a'))->toBeTrue();
    expect($registry->userHasRoleInScope($user, $role->slug, 'faculty', 'faculty-b'))->toBeTrue();
    expect($registry->userHasRoleInScope($user, $role->slug, 'faculty', 'faculty-c'))->toBeFalse();
});

test('revoking a scoped role grant leaves the grant under a different scope intact', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();

    app(AssignRoleAction::class)->execute($user, $role, scopeType: 'faculty', scopeId: 'faculty-a');
    app(AssignRoleAction::class)->execute($user, $role, scopeType: 'faculty', scopeId: 'faculty-b');

    app(RevokeRoleAction::class)->execute($user, $role, scopeType: 'faculty', scopeId: 'faculty-a');

    $registry = app(PermissionRegistry::class);

    expect($registry->userHasRoleInScope($user, $role->slug, 'faculty', 'faculty-a'))->toBeFalse();
    expect($registry->userHasRoleInScope($user, $role->slug, 'faculty', 'faculty-b'))->toBeTrue();
});
