<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
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

function grantAcademicPermission(User $user, University $university, array $permissionSlugs): void
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

test('lecturers/employees/study-programs/class-sections all require their own read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantAcademicPermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/lecturers')->assertApiError(403);
    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/employees')->assertApiError(403);
    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/study-programs')->assertApiError(403);
    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/class-sections')->assertApiError(403);
});

test('a permitted user can list lecturers filtered by faculty and see the study program counts', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $facultyA = Faculty::factory()->create(['university_id' => $university->id]);
    $facultyB = Faculty::factory()->create(['university_id' => $university->id]);
    Lecturer::factory()->count(2)->create(['university_id' => $university->id, 'faculty_id' => $facultyA->id]);
    Lecturer::factory()->create(['university_id' => $university->id, 'faculty_id' => $facultyB->id]);

    $program = StudyProgram::factory()->create(['university_id' => $university->id, 'faculty_id' => $facultyA->id]);
    Employee::factory()->create(['university_id' => $university->id, 'unit_kerja' => 'Keuangan']);

    $term = AcademicTerm::factory()->create(['university_id' => $university->id, 'is_current' => true]);
    ClassSection::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'academic_term_id' => $term->id, 'is_active' => true]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantAcademicPermission($user, $university, ['lecturers.read', 'employees.read', 'study_programs.read', 'classes.read']);

    $lecturers = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/lecturers?filter[faculty_id]='.$facultyA->id)
        ->assertApiSuccess();
    expect($lecturers->json('meta.total'))->toBe(2);

    $employees = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/employees?filter[unit_kerja]=Keuangan')
        ->assertApiSuccess();
    expect($employees->json('meta.total'))->toBe(1);

    $programs = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/study-programs')
        ->assertApiSuccess();
    expect($programs->json('data.0.students_count'))->toBe(0);
    expect($programs->json('data.0.class_sections_count'))->toBe(1);

    $classes = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/class-sections?filter[is_active]=1')
        ->assertApiSuccess();
    expect($classes->json('meta.total'))->toBe(1);
});
