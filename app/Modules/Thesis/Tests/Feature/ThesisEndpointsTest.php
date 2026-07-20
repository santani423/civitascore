<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\Thesis\Enums\ThesisStatus;
use Modules\Thesis\Models\Thesis;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

function grantThesisPermission(User $user, University $university, array $permissionSlugs): void
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

test('theses require theses.read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantThesisPermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/theses')->assertApiError(403);
});

test('a permitted user can list theses filtered by status and see the supervisor name', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $lecturer = Lecturer::factory()->create(['university_id' => $university->id]);
    $student = Student::factory()->create(['university_id' => $university->id]);
    Thesis::factory()->create([
        'university_id' => $university->id,
        'student_id' => $student->id,
        'supervisor_lecturer_id' => $lecturer->id,
        'status' => ThesisStatus::Sidang,
    ]);
    Thesis::factory()->create(['university_id' => $university->id, 'status' => ThesisStatus::Bimbingan]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantThesisPermission($user, $university, ['theses.read']);

    $response = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/theses?filter[status]=sidang')
        ->assertApiSuccess();
    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('data.0.supervisor_name'))->toBe($lecturer->name);
});
