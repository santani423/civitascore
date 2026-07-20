<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Announcement\Enums\AnnouncementTargetScope;
use Modules\Announcement\Models\Announcement;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

function grantAnnouncementPermission(User $user, University $university, array $permissionSlugs): void
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

test('announcements require announcements.read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantAnnouncementPermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/announcements')->assertApiError(403);
});

test('a permitted user can list announcements pinned-first and see the creator name', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $author = User::factory()->create(['name' => 'Admin Akademik']);
    Announcement::factory()->create([
        'university_id' => $university->id,
        'title' => 'Biasa',
        'is_pinned' => false,
        'published_at' => now()->subDay(),
        'target_scope' => AnnouncementTargetScope::Universitas,
    ]);
    Announcement::factory()->create([
        'university_id' => $university->id,
        'title' => 'Penting',
        'is_pinned' => true,
        'published_at' => now()->subWeek(),
        'created_by' => $author->id,
        'target_scope' => AnnouncementTargetScope::Universitas,
    ]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantAnnouncementPermission($user, $university, ['announcements.read']);

    $response = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/announcements')
        ->assertApiSuccess();
    expect($response->json('data.0.title'))->toBe('Penting');
    expect($response->json('data.0.creator_name'))->toBe('Admin Akademik');
});
