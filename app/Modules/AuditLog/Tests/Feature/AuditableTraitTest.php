<?php

use App\Models\User;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Models\AuditLog;
use Modules\UserManagement\Models\Role;

test('creating a role writes a created audit log entry', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $role = Role::factory()->create();

    $log = AuditLog::query()
        ->where('auditable_type', $role->getMorphClass())
        ->where('auditable_id', $role->id)
        ->where('action', AuditAction::Created)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($user->id);
    expect($log->new_values['name'])->toBe($role->name);
});

test('updating a role writes an updated audit log entry with old and new values', function () {
    $role = Role::factory()->create(['name' => 'Original Name']);

    $role->update(['name' => 'Renamed']);

    $log = AuditLog::query()
        ->where('auditable_type', $role->getMorphClass())
        ->where('auditable_id', $role->id)
        ->where('action', AuditAction::Updated)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->old_values['name'])->toBe('Original Name');
    expect($log->new_values['name'])->toBe('Renamed');
});

test('a console-context change records a null user_id', function () {
    $role = Role::factory()->create();

    $log = AuditLog::query()
        ->where('auditable_type', $role->getMorphClass())
        ->where('auditable_id', $role->id)
        ->first();

    expect($log->user_id)->toBeNull();
});

test('a "reason" field in the request body is captured on the audit log entry', function () {
    actingAsUserWithPermissions(['roles.create', 'roles.update']);

    $store = $this->postJson('/api/v1/roles', ['name' => 'Dosen Pembimbing']);
    $roleId = $store->json('data.id');

    $this->putJson("/api/v1/roles/{$roleId}", [
        'name' => 'Dosen Pembimbing Akademik',
        'reason' => 'Penyesuaian nama sesuai SK terbaru.',
    ]);

    $log = AuditLog::query()
        ->where('auditable_type', Role::class)
        ->where('auditable_id', $roleId)
        ->where('action', AuditAction::Updated)
        ->first();

    expect($log->reason)->toBe('Penyesuaian nama sesuai SK terbaru.');
});
