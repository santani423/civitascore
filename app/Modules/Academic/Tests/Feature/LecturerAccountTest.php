<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Hash;
use Modules\Academic\Database\Seeders\LecturerUserAccountSeeder;
use Modules\Academic\Models\Lecturer;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Database\Seeders\OrganizationalRoleSeeder;
use Modules\UserManagement\Database\Seeders\RolePermissionSeeder;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/**
 * End-to-end coverage of the Lecturer <-> User identity link: SDM creates a
 * dosen -> account provisioned with the real `lecturer` role -> the dosen
 * logs in, sees only lecturer features, manages their profile and logs out.
 * Uses the real organizational role seeders so the permission set under
 * test is exactly what production grants.
 */
beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, OrganizationalRoleSeeder::class]);
});

function makeSdmUser(University $university, string $roleSlug = 'hr_administrator'): User
{
    $user = User::factory()->create();

    UserUniversity::query()->create([
        'user_id' => $user->id,
        'university_id' => $university->id,
        'membership_type' => MembershipType::Staff,
        'status' => MembershipStatus::Active,
        'joined_at' => now(),
        'is_default' => true,
    ]);

    UserRole::query()->create([
        'user_id' => $user->id,
        'role_id' => Role::query()->where('slug', $roleSlug)->whereNull('university_id')->value('id'),
        'university_id' => $university->id,
        'assigned_at' => now(),
    ]);

    return $user;
}

function makeLinkedLecturer(University $university, array $userOverrides = []): Lecturer
{
    app(TenantContext::class)->setUniversityId($university->id);
    $lecturer = Lecturer::factory()->create(['university_id' => $university->id]);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create(array_merge(['email' => $lecturer->email], $userOverrides));

    UserUniversity::query()->create([
        'user_id' => $user->id,
        'university_id' => $university->id,
        'membership_type' => MembershipType::Lecturer,
        'status' => MembershipStatus::Active,
        'joined_at' => now(),
        'is_default' => true,
    ]);

    UserRole::query()->create([
        'user_id' => $user->id,
        'role_id' => Role::query()->where('slug', 'lecturer')->whereNull('university_id')->value('id'),
        'university_id' => $university->id,
        'assigned_at' => now(),
    ]);

    $lecturer->forceFill(['user_id' => $user->id])->saveQuietly();

    return $lecturer->refresh();
}

function lecturerRoleId(): string
{
    return Role::query()->where('slug', 'lecturer')->whereNull('university_id')->value('id');
}

test('full flow: SDM creates a dosen, the dosen logs in, manages their profile and logs out', function () {
    $university = University::factory()->create();
    $sdm = makeSdmUser($university);

    // 1. Create lecturer -> account is provisioned automatically.
    $create = $this->actingAs($sdm)
        ->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', [
            'nidn' => '0011223344',
            'nip' => '198001012010011001',
            'name' => 'Dr. Siti Rahma',
            'email' => 'siti.rahma@kampus.test',
            'employment_status' => 'permanent',
            'functional_rank' => 'lektor',
            'highest_education' => 's3',
            'hired_at' => '2015-08-01',
        ]);

    $create->assertApiSuccess(201);
    expect($create->json('data.has_account'))->toBeTrue();
    expect($create->json('data.account.must_change_password'))->toBeTrue();
    expect($create->json('data.functional_rank'))->toBe('lektor');
    expect($create->json('meta.credentials.account_created'))->toBeTrue();
    $initialPassword = $create->json('meta.credentials.password');
    expect($initialPassword)->toBeString()->not->toBeEmpty();

    // 2. Exactly one user, linked, with the lecturer role + membership in this tenant.
    $user = User::query()->where('email', 'siti.rahma@kampus.test')->sole();
    expect(Lecturer::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(1);
    expect(UserRole::query()->where('user_id', $user->id)->where('role_id', lecturerRoleId())->where('university_id', $university->id)->exists())->toBeTrue();
    expect(UserUniversity::query()->where('user_id', $user->id)->where('university_id', $university->id)->value('membership_type'))->toBe(MembershipType::Lecturer);

    // 3. Login with the issued credentials.
    app('auth')->forgetGuards();
    $login = $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/login', ['email' => 'siti.rahma@kampus.test', 'password' => $initialPassword]);
    $login->assertApiSuccess();
    expect($login->json('data.user.must_change_password'))->toBeTrue();
    $token = $login->json('data.token');

    $asLecturer = fn () => $this->withToken($token)->withHeader('X-University-ID', $university->id);

    // 4. Forced password change.
    $asLecturer()->postJson('/api/v1/change-password', [
        'current_password' => $initialPassword,
        'password' => 'PasswordBaru123',
        'password_confirmation' => 'PasswordBaru123',
    ])->assertApiSuccess();
    expect($user->refresh()->must_change_password)->toBeFalse();

    // 5. Lecturer role & permissions, dashboard reachable.
    $me = $asLecturer()->getJson('/api/v1/me');
    expect($me->json('data.roles'))->toBe(['lecturer']);
    expect($me->json('data.permissions'))->toContain('lecturer_profile.read', 'grades.create')
        ->not->toContain('lecturers.read', 'lecturers.update', 'user_roles.create');
    $asLecturer()->getJson('/api/v1/dashboard')->assertApiSuccess();

    // 6. Only lecturer features — SDM endpoints are forbidden.
    $asLecturer()->getJson('/api/v1/lecturers')->assertApiError(403);
    $asLecturer()->postJson('/api/v1/lecturers', ['nidn' => '1', 'name' => 'x', 'create_account' => false])->assertApiError(403);
    $lecturerId = $create->json('data.id');
    $asLecturer()->postJson("/api/v1/lecturers/{$lecturerId}/account/reset-password")->assertApiError(403);

    // 7. Own profile.
    $profile = $asLecturer()->getJson('/api/v1/lecturer-profile');
    $profile->assertApiSuccess();
    expect($profile->json('data.id'))->toBe($lecturerId);
    expect($profile->json('data.nidn'))->toBe('0011223344');

    $asLecturer()->putJson('/api/v1/lecturer-profile', ['phone' => '081234567890', 'name' => 'Hacked', 'nidn' => '999'])
        ->assertApiSuccess();
    $lecturer = Lecturer::withoutGlobalScopes()->find($lecturerId);
    expect($lecturer->phone)->toBe('081234567890');
    expect($lecturer->name)->toBe('Dr. Siti Rahma');
    expect($lecturer->nidn)->toBe('0011223344');

    // 8. Logout revokes the token.
    $asLecturer()->postJson('/api/v1/logout')->assertApiSuccess();
    expect($user->tokens()->count())->toBe(0);
    app('auth')->forgetGuards();
    $asLecturer()->getJson('/api/v1/me')->assertApiError(401);
});

test('creating a lecturer without an email is rejected when an account is requested', function () {
    $university = University::factory()->create();

    $this->actingAs(makeSdmUser($university))
        ->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', ['nidn' => '1234', 'name' => 'Dosen Tanpa Email'])
        ->assertApiError(422)
        ->assertJsonValidationErrors('email');
});

test('a lecturer can be created without an account and provisioned later', function () {
    $university = University::factory()->create();
    $sdm = makeSdmUser($university);

    $create = $this->actingAs($sdm)
        ->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', ['nidn' => '1234', 'name' => 'Dosen', 'create_account' => false]);
    $create->assertApiSuccess(201);
    expect($create->json('data.has_account'))->toBeFalse();
    expect($create->json('meta'))->toBe([]);

    $id = $create->json('data.id');

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/lecturers/{$id}/account")
        ->assertApiError(422);

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/lecturers/{$id}", ['email' => 'dosen@kampus.test'])
        ->assertApiSuccess();

    $provision = $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/lecturers/{$id}/account", ['password' => 'Rahasia123']);
    $provision->assertApiSuccess(201);
    expect($provision->json('meta.credentials.password'))->toBe('Rahasia123');
    expect(Hash::check('Rahasia123', User::query()->where('email', 'dosen@kampus.test')->value('password')))->toBeTrue();

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/lecturers/{$id}/account")
        ->assertApiError(409);
});

test('an existing user with the same email is linked, not duplicated, and keeps their password', function () {
    $university = University::factory()->create();
    $existing = User::factory()->create(['email' => 'lama@kampus.test', 'password' => Hash::make('PasswordLama1')]);

    $create = $this->actingAs(makeSdmUser($university))
        ->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', ['nidn' => '555', 'name' => 'Dosen Lama', 'email' => 'lama@kampus.test']);

    $create->assertApiSuccess(201);
    expect($create->json('data.user_id'))->toBe($existing->id);
    expect($create->json('meta.credentials.account_created'))->toBeFalse();
    expect($create->json('meta.credentials.password'))->toBeNull();
    expect(User::query()->where('email', 'lama@kampus.test')->count())->toBe(1);
    expect(Hash::check('PasswordLama1', $existing->refresh()->password))->toBeTrue();
});

test('a user already linked to another lecturer cannot be linked twice', function () {
    $university = University::factory()->create();
    $linked = makeLinkedLecturer($university);

    $this->actingAs(makeSdmUser($university))
        ->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', ['nidn' => '777', 'name' => 'Duplikat', 'email' => $linked->email])
        ->assertApiError(422)
        ->assertJsonValidationErrors('email');
});

test('SDM can reset the password, which forces a change and signs the dosen out', function () {
    $university = University::factory()->create();
    $lecturer = makeLinkedLecturer($university, ['must_change_password' => false]);
    $lecturer->user->createToken('api');

    $response = $this->actingAs(makeSdmUser($university))
        ->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/lecturers/{$lecturer->id}/account/reset-password");

    $response->assertApiSuccess();
    $user = $lecturer->user->refresh();
    expect(Hash::check($response->json('meta.credentials.password'), $user->password))->toBeTrue();
    expect($user->must_change_password)->toBeTrue();
    expect($user->tokens()->count())->toBe(0);
});

test('SDM cannot manage an account that also holds a privileged role', function () {
    $university = University::factory()->create();
    $lecturer = makeLinkedLecturer($university);

    UserRole::query()->create([
        'user_id' => $lecturer->user_id,
        'role_id' => Role::query()->where('slug', 'university_administrator')->value('id'),
        'university_id' => $university->id,
        'assigned_at' => now(),
    ]);

    $sdm = makeSdmUser($university);

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/lecturers/{$lecturer->id}/account/reset-password")
        ->assertApiError(403);

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/lecturers/{$lecturer->id}/account/status", ['is_active' => false])
        ->assertApiError(403);

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/lecturers/{$lecturer->id}", ['email' => 'baru@kampus.test'])
        ->assertApiError(422);

    $show = $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/lecturers/{$lecturer->id}");
    expect($show->json('data.account.manageable'))->toBeFalse();
    expect($lecturer->user->refresh()->is_active)->toBeTrue();
});

test('deactivating the account blocks login and revokes sessions; reactivating restores it', function () {
    $university = University::factory()->create();
    $lecturer = makeLinkedLecturer($university, ['password' => Hash::make('Rahasia123')]);
    $lecturer->user->createToken('api');
    $sdm = makeSdmUser($university);

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/lecturers/{$lecturer->id}/account/status", ['is_active' => false])
        ->assertApiSuccess();

    $user = $lecturer->user->refresh();
    expect($user->is_active)->toBeFalse();
    expect($user->tokens()->count())->toBe(0);
    expect(UserUniversity::query()->where('user_id', $user->id)->value('status'))->toBe(MembershipStatus::Inactive);
    // Lecturer master record itself stays active.
    expect(Lecturer::withoutGlobalScopes()->find($lecturer->id)->is_active)->toBeTrue();

    app('auth')->forgetGuards();
    $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'Rahasia123'])
        ->assertApiError(403);

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/lecturers/{$lecturer->id}/account/status", ['is_active' => true])
        ->assertApiSuccess();

    expect($user->refresh()->is_active)->toBeTrue();
    expect(UserUniversity::query()->where('user_id', $user->id)->value('status'))->toBe(MembershipStatus::Active);
});

test('editing the lecturer keeps the login account in sync', function () {
    $university = University::factory()->create();
    $lecturer = makeLinkedLecturer($university);
    $sdm = makeSdmUser($university);

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/lecturers/{$lecturer->id}", ['name' => 'Prof. Nama Baru', 'email' => 'baru@kampus.test'])
        ->assertApiSuccess();

    $user = $lecturer->user->refresh();
    expect($user->name)->toBe('Prof. Nama Baru');
    expect($user->email)->toBe('baru@kampus.test');

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/lecturers/{$lecturer->id}", ['is_active' => false])
        ->assertApiSuccess();
    expect($user->refresh()->is_active)->toBeFalse();
});

test('changing a lecturer email to one used by another account is rejected', function () {
    $university = University::factory()->create();
    $lecturer = makeLinkedLecturer($university);
    User::factory()->create(['email' => 'terpakai@kampus.test']);

    $this->actingAs(makeSdmUser($university))->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/lecturers/{$lecturer->id}", ['email' => 'terpakai@kampus.test'])
        ->assertApiError(422)
        ->assertJsonValidationErrors('email');

    $this->actingAs(makeSdmUser($university))->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/lecturers/{$lecturer->id}", ['email' => null])
        ->assertApiError(422);
});

test('deleting a lecturer soft-deletes it, revokes the role and disables the account; re-creating restores it', function () {
    $university = University::factory()->create();
    $lecturer = makeLinkedLecturer($university);
    $userId = $lecturer->user_id;
    $sdm = makeSdmUser($university);

    $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->deleteJson("/api/v1/lecturers/{$lecturer->id}")
        ->assertApiSuccess();

    $this->assertSoftDeleted('lecturers', ['id' => $lecturer->id]);
    expect(UserRole::query()->where('user_id', $userId)->where('role_id', lecturerRoleId())->exists())->toBeFalse();
    expect(User::query()->find($userId)->is_active)->toBeFalse();

    $recreate = $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/lecturers', ['nidn' => $lecturer->nidn, 'name' => $lecturer->name, 'email' => $lecturer->email]);

    $recreate->assertApiSuccess(201);
    expect($recreate->json('data.id'))->toBe($lecturer->id);
    expect($recreate->json('data.user_id'))->toBe($userId);
    expect(User::query()->find($userId)->is_active)->toBeTrue();
    expect(UserRole::query()->where('user_id', $userId)->where('role_id', lecturerRoleId())->exists())->toBeTrue();
});

test('the lecturer profile is isolated per tenant and requires a linked record', function () {
    $university = University::factory()->create();
    $other = University::factory()->create();
    $lecturer = makeLinkedLecturer($university);

    $this->actingAs($lecturer->user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/lecturer-profile')
        ->assertApiSuccess();

    // No lecturer role in the other tenant -> no permission there.
    $this->actingAs($lecturer->user)->withHeader('X-University-ID', $other->id)
        ->getJson('/api/v1/lecturer-profile')
        ->assertApiError(403);

    // Role holder without a linked lecturer row.
    $unlinked = makeSdmUser($university, 'lecturer');
    $this->actingAs($unlinked)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/lecturer-profile')
        ->assertApiError(404);
});

test('lecturer list supports account and employment filters', function () {
    $university = University::factory()->create();
    makeLinkedLecturer($university);
    app(TenantContext::class)->setUniversityId($university->id);
    Lecturer::factory()->create(['university_id' => $university->id, 'employment_status' => 'contract']);
    app(TenantContext::class)->setUniversityId(null);
    $sdm = makeSdmUser($university);

    $withAccount = $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/lecturers?filter[has_account]=1');
    expect($withAccount->json('data'))->toHaveCount(1);
    expect($withAccount->json('data.0.account.is_active'))->toBeTrue();

    $contract = $this->actingAs($sdm)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/lecturers?filter[employment_status]=contract');
    expect($contract->json('data'))->toHaveCount(1);
    expect($contract->json('data.0.has_account'))->toBeFalse();
});

test('faculty options are available to SDM', function () {
    $university = University::factory()->create();

    $this->actingAs(makeSdmUser($university))->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/faculties')
        ->assertApiSuccess();
});

test('the backfill seeder links existing lecturers and role-only dosen accounts', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);
    $unlinked = Lecturer::factory()->create(['university_id' => $university->id, 'email' => 'unlinked@kampus.test']);
    app(TenantContext::class)->setUniversityId(null);
    $demoDosen = makeSdmUser($university, 'lecturer');

    $this->seed(LecturerUserAccountSeeder::class);
    $this->seed(LecturerUserAccountSeeder::class); // idempotent

    $user = User::query()->where('email', 'unlinked@kampus.test')->sole();
    expect(Lecturer::withoutGlobalScopes()->find($unlinked->id)->user_id)->toBe($user->id);
    expect($user->must_change_password)->toBeTrue();
    expect(Lecturer::withoutGlobalScopes()->where('user_id', $demoDosen->id)->count())->toBe(1);
});
