<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Student;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

function grantReportPermission(User $user, University $university, array $permissionSlugs): void
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

test('reports require reports.read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantReportPermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/reports')->assertApiError(403);
    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/reports/mahasiswa')->assertApiError(403);
});

test('a permitted user can list report types and fetch the mahasiswa report as json', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);
    Student::factory()->count(3)->create(['university_id' => $university->id]);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantReportPermission($user, $university, ['reports.read']);

    $types = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/reports')
        ->assertApiSuccess();
    expect(collect($types->json('data'))->pluck('type')->all())->toBe(['mahasiswa', 'akademik', 'keuangan', 'sdm']);

    $report = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/reports/mahasiswa')
        ->assertApiSuccess();
    expect($report->json('data.total_rows'))->toBe(3);
    expect($report->json('data.summary.total'))->toBe(3);
});

test('an unknown report type returns 404', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantReportPermission($user, $university, ['reports.read']);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/reports/tidak-ada')
        ->assertStatus(404);
});

test('a permitted user can export a report as csv', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);
    Student::factory()->create(['university_id' => $university->id, 'name' => 'Csv Export Test']);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantReportPermission($user, $university, ['reports.read']);

    $response = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->get('/api/v1/reports/mahasiswa?export=csv');

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
    expect($response->getContent())->toContain('Csv Export Test');
});
