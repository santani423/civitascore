<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Student;
use Modules\Internship\Enums\InternshipProgramType;
use Modules\Internship\Models\Internship;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

function grantInternshipPermission(User $user, University $university, array $permissionSlugs): void
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

test('internships require internships.read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantInternshipPermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/internships')->assertApiError(403);
});

test('a permitted user can list internships filtered by program type', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $student = Student::factory()->create(['university_id' => $university->id]);
    Internship::factory()->create(['university_id' => $university->id, 'student_id' => $student->id, 'program_type' => InternshipProgramType::Mbkm, 'sks_converted' => 4]);
    Internship::factory()->create(['university_id' => $university->id, 'program_type' => InternshipProgramType::Magang]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantInternshipPermission($user, $university, ['internships.read']);

    $response = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/internships?filter[program_type]=mbkm')
        ->assertApiSuccess();
    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('data.0.sks_converted'))->toBe(4);
});
