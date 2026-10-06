<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/**
 * Lecturers are TenantScoped (university_id is NOT NULL) — every factory
 * call here needs a resolved TenantContext, same pattern as
 * StudentControllerTest's grantStudentPermission().
 */
function grantLecturerPermission(User $user, University $university, array $permissionSlugs): void
{
    UserUniversity::query()->create([
        'user_id' => $user->id,
        'university_id' => $university->id,
        'membership_type' => MembershipType::Admin,
        'status' => MembershipStatus::Active,
        'joined_at' => now(),
        'is_default' => true,
    ]);

    $role = Role::factory()->create(['university_id' => $university->id]);

    $permissionIds = collect($permissionSlugs)->map(function (string $slug) {
        [$resource, $action] = explode('.', $slug, 2);

        return Permission::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'resource' => $resource, 'action' => PermissionAction::from($action), 'scope' => PermissionScope::Data],
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

test('creating a lecturer requires the lecturers.create permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantLecturerPermission($user, $university, ['lecturers.read']);

    $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', [])
        ->assertApiError(403);
});

test('a permitted user can create a lecturer', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantLecturerPermission($user, $university, ['lecturers.create']);

    $response = $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', [
            'nidn' => '1234567890',
            'name' => 'Dr. Dosen Baru',
            'email' => 'dosen.baru@example.com',
            'is_active' => true,
        ]);

    $response->assertApiSuccess(201);
    expect($response->json('data.name'))->toBe('Dr. Dosen Baru');
    expect($response->json('data.nidn'))->toBe('1234567890');

    $this->assertDatabaseHas('lecturers', [
        'university_id' => $university->id,
        'nidn' => '1234567890',
        'name' => 'Dr. Dosen Baru',
    ]);
});

test('creating a lecturer rejects a NIDN already used at the same university', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);
    Lecturer::factory()->create(['university_id' => $university->id, 'nidn' => '1234567890']);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantLecturerPermission($user, $university, ['lecturers.create']);

    $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', ['nidn' => '1234567890', 'name' => 'Dosen Lain'])
        ->assertApiError(422);
});

test('creating a lecturer rejects a faculty_id belonging to another university', function () {
    $university = University::factory()->create();
    $otherUniversity = University::factory()->create();
    app(TenantContext::class)->setUniversityId($otherUniversity->id);
    $foreignFaculty = Faculty::factory()->create(['university_id' => $otherUniversity->id]);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantLecturerPermission($user, $university, ['lecturers.create']);

    $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', [
            'faculty_id' => $foreignFaculty->id,
            'nidn' => '1234567890',
            'name' => 'Dosen',
        ])
        ->assertNotFound();
});

test('updating a lecturer requires the lecturers.update permission', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);
    $lecturer = Lecturer::factory()->create(['university_id' => $university->id]);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantLecturerPermission($user, $university, ['lecturers.read']);

    $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/lecturers/{$lecturer->id}", ['name' => 'Nama Baru'])
        ->assertApiError(403);
});

test('a permitted user can update a lecturer', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);
    $lecturer = Lecturer::factory()->create(['university_id' => $university->id, 'is_active' => true]);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantLecturerPermission($user, $university, ['lecturers.update']);

    $response = $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/lecturers/{$lecturer->id}", ['name' => 'Nama Diperbarui', 'is_active' => false]);

    $response->assertApiSuccess();
    expect($response->json('data.name'))->toBe('Nama Diperbarui');
    expect($response->json('data.is_active'))->toBeFalse();
});

test('a permitted user can delete a lecturer', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);
    $lecturer = Lecturer::factory()->create(['university_id' => $university->id]);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantLecturerPermission($user, $university, ['lecturers.delete']);

    $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->deleteJson("/api/v1/lecturers/{$lecturer->id}")
        ->assertApiSuccess();

    $this->assertDatabaseMissing('lecturers', ['id' => $lecturer->id]);
});
