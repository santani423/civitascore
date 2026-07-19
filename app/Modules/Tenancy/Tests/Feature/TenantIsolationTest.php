<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\FileManagement\Models\FileUpload;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/**
 * Grants $user a role (scoped to $university) carrying exactly the given
 * permission slugs, and an active membership in that university.
 */
function grantTenantScopedPermissions(User $user, University $university, array $permissionSlugs): void
{
    UserUniversity::query()->create([
        'user_id' => $user->id,
        'university_id' => $university->id,
        'membership_type' => MembershipType::Admin,
        'status' => MembershipStatus::Active,
        'joined_at' => now(),
        'is_default' => false,
    ]);

    $role = Role::factory()->create(['university_id' => $university->id]);

    $permissionIds = collect($permissionSlugs)->map(function (string $slug) {
        [$resource, $action] = explode('.', $slug, 2);

        return Permission::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'resource' => $resource, 'action' => PermissionAction::from($action), 'scope' => \Modules\UserManagement\Enums\PermissionScope::Data],
        )->id;
    });

    $role->permissions()->sync($permissionIds);

    UserRole::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'university_id' => $university->id,
        'assigned_at' => now(),
    ]);
}

test('a permission granted in one university does not apply when resolved into another university', function () {
    $user = User::factory()->create();
    $universityA = University::factory()->create();
    $universityB = University::factory()->create();

    grantTenantScopedPermissions($user, $universityA, ['audit_logs.read']);

    $tenant = app(TenantContext::class);

    $tenant->setUniversityId($universityA->id);
    expect($user->hasPermissionTo('audit_logs.read'))->toBeTrue();

    $tenant->setUniversityId($universityB->id);
    expect($user->hasPermissionTo('audit_logs.read'))->toBeFalse();

    $tenant->setUniversityId(null);
    expect($user->hasPermissionTo('audit_logs.read'))->toBeFalse();
});

test('a file upload belonging to one university is invisible while resolved into another', function () {
    $universityA = University::factory()->create();
    $universityB = University::factory()->create();

    $uploader = User::factory()->create();
    $tenant = app(TenantContext::class);

    $tenant->setUniversityId($universityA->id);
    $file = FileUpload::factory()->create(['uploaded_by' => $uploader->id]);
    expect($file->university_id)->toBe($universityA->id);

    expect(FileUpload::query()->find($file->id))->not->toBeNull();

    $tenant->setUniversityId($universityB->id);
    expect(FileUpload::query()->find($file->id))->toBeNull();

    $tenant->setUniversityId(null);
    expect(FileUpload::query()->find($file->id))->not->toBeNull();
});

test('a tenant admin can read/update their own custom role but not another tenant\'s custom role', function () {
    $universityA = University::factory()->create();
    $universityB = University::factory()->create();

    $adminA = User::factory()->create();
    grantTenantScopedPermissions($adminA, $universityA, ['roles.read', 'roles.update']);

    $roleInA = Role::factory()->create(['university_id' => $universityA->id]);
    $roleInB = Role::factory()->create(['university_id' => $universityB->id]);

    // Own tenant's custom role: readable and updatable.
    $this->actingAs($adminA)->withHeader('X-University-ID', $universityA->id)
        ->getJson("/api/v1/roles/{$roleInA->id}")
        ->assertApiSuccess();

    $this->actingAs($adminA)->withHeader('X-University-ID', $universityA->id)
        ->putJson("/api/v1/roles/{$roleInA->id}", ['name' => 'Diubah Admin A'])
        ->assertApiSuccess();

    // Another tenant's custom role: forbidden, even though adminA has
    // roles.read/update — because they hold no grant scoped to University B.
    $this->actingAs($adminA)->withHeader('X-University-ID', $universityB->id)
        ->getJson("/api/v1/roles/{$roleInB->id}")
        ->assertApiError(403);
});

test('EnsureUniversityAccessMiddleware blocks a user with no membership in the resolved university', function () {
    $user = User::factory()->create();
    $university = University::factory()->create();

    $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/tenant/profile')
        ->assertApiError(403);
});

test('EnsureUniversityAccessMiddleware allows a member and returns their own university profile', function () {
    $user = User::factory()->create();
    $university = University::factory()->create(['name' => 'Universitas Contoh Isolasi']);

    UserUniversity::query()->create([
        'user_id' => $user->id,
        'university_id' => $university->id,
        'membership_type' => MembershipType::Staff,
        'status' => MembershipStatus::Active,
        'joined_at' => now(),
        'is_default' => true,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/tenant/profile')
        ->assertApiSuccess();

    expect($response->json('data.id'))->toBe($university->id);
});

test('a super_admin bypasses tenant membership on tenant.access routes', function () {
    $superAdmin = User::factory()->create();
    $superAdminRole = Role::query()->firstOrCreate(['slug' => 'super_admin', 'university_id' => null], ['name' => 'Super Admin', 'is_system' => true]);
    UserRole::query()->create(['user_id' => $superAdmin->id, 'role_id' => $superAdminRole->id, 'assigned_at' => now()]);

    $university = University::factory()->create();

    // No UserUniversity membership row exists for $superAdmin at all.
    $this->actingAs($superAdmin)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/tenant/profile')
        ->assertApiSuccess();
});

test('a user who is a member of two universities sees different data depending on which is selected', function () {
    $user = User::factory()->create();
    $universityA = University::factory()->create(['name' => 'Universitas A']);
    $universityB = University::factory()->create(['name' => 'Universitas B']);

    foreach ([$universityA, $universityB] as $university) {
        UserUniversity::query()->create([
            'user_id' => $user->id,
            'university_id' => $university->id,
            'membership_type' => MembershipType::Staff,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'is_default' => false,
        ]);
    }

    $this->actingAs($user)->withHeader('X-University-ID', $universityA->id)
        ->getJson('/api/v1/tenant/profile')
        ->assertApiSuccess()
        ->assertJsonPath('data.id', $universityA->id);

    $this->actingAs($user)->withHeader('X-University-ID', $universityB->id)
        ->getJson('/api/v1/tenant/profile')
        ->assertApiSuccess()
        ->assertJsonPath('data.id', $universityB->id);
});
