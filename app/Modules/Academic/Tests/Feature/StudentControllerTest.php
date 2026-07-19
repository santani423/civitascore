<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
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
 * Students are TenantScoped (university_id is NOT NULL) — every factory
 * call here needs a resolved TenantContext, same pattern as
 * DashboardStatsTest's seedAcademicFixture()/grantDashboardPermissions().
 */
function grantStudentPermission(User $user, University $university, array $permissionSlugs): void
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

test('listing students requires the students.read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantStudentPermission($user, $university, []);

    $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/students')
        ->assertApiError(403);
});

test('a permitted user can list, search, and paginate students', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $faculty = Faculty::factory()->create(['university_id' => $university->id]);
    $program = StudyProgram::factory()->create(['university_id' => $university->id, 'faculty_id' => $faculty->id]);

    Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'name' => 'Budi Santoso', 'nim' => '20260001']);
    Student::factory()->count(2)->create(['university_id' => $university->id, 'study_program_id' => $program->id]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantStudentPermission($user, $university, ['students.read']);

    $response = $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/students?search=Budi');

    $response->assertApiSuccess();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.name'))->toBe('Budi Santoso');
    expect($response->json('data.0.study_program_name'))->toBe($program->name);
});
