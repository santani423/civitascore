<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Alumni\Enums\AlumniEmploymentStatus;
use Modules\Alumni\Models\Alumni;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

function grantAlumniPermission(User $user, University $university, array $permissionSlugs): void
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

test('alumni require alumni.read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantAlumniPermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/alumni')->assertApiError(403);
});

test('a permitted user can list alumni and see the study program via the student relation', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id, 'name' => 'Teknik Informatika']);
    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    Alumni::factory()->create([
        'university_id' => $university->id,
        'student_id' => $student->id,
        'employment_status' => AlumniEmploymentStatus::Bekerja,
    ]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantAlumniPermission($user, $university, ['alumni.read']);

    $response = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/alumni?filter[employment_status]=bekerja')
        ->assertApiSuccess();
    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('data.0.study_program_name'))->toBe('Teknik Informatika');
});
