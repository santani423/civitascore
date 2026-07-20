<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\StudyProgram;
use Modules\Scholarship\Enums\ScholarshipApplicationStatus;
use Modules\Scholarship\Models\Scholarship;
use Modules\Scholarship\Models\ScholarshipApplication;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

function grantScholarshipPermission(User $user, University $university, array $permissionSlugs): void
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

test('scholarships require scholarships.read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantScholarshipPermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/scholarships')->assertApiError(403);
});

test('a permitted user can list and view scholarships with embedded applications', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $scholarship = Scholarship::factory()->create(['university_id' => $university->id]);
    ScholarshipApplication::factory()->create([
        'university_id' => $university->id,
        'scholarship_id' => $scholarship->id,
        'status' => ScholarshipApplicationStatus::Approved,
        'student_id' => \Modules\Academic\Models\Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]),
    ]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantScholarshipPermission($user, $university, ['scholarships.read']);

    $list = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/scholarships')
        ->assertApiSuccess();
    expect($list->json('data.0.applications_count'))->toBe(1);

    $detail = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/scholarships/'.$scholarship->id)
        ->assertApiSuccess();
    expect($detail->json('data.applications'))->toHaveCount(1);
    expect($detail->json('data.applications.0.status'))->toBe('approved');
});
