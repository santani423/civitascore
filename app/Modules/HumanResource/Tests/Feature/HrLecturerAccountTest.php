<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Lecturer;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

require_once __DIR__.'/../Support/hr_helpers.php';

/*
| Dosen yang ditambahkan Bagian SDM lewat Modul SDM (POST hr/employees)
| langsung punya akun login ber-role `lecturer` — satu akun yang sama
| tertaut di lecturers.user_id (Modul Akademik) dan employees.user_id
| (layanan mandiri SDM), dan ikut nonaktif saat pegawainya dinonaktifkan.
*/

beforeEach(function () {
    hrSeedRoles();
    $this->university = University::factory()->create();
    $this->sdm = hrUserWithRole($this->university, 'hr_administrator');
});

function hrCreateLecturer(object $test, array $overrides = []): TestResponse
{
    return $test->actingAs($test->sdm)->withHeaders(hrHeaders($test->university))
        ->postJson('/api/v1/hr/employees', [
            'employee_type' => 'lecturer',
            'name' => 'Dr. Budi Santoso',
            'email' => 'budi.santoso@kampus.test',
            'nidn' => '0123456789',
            'employment_status' => 'permanent',
            ...$overrides,
        ]);
}

function hrHoldsLecturerRole(User $user, University $university): bool
{
    return UserRole::query()
        ->where('user_id', $user->id)
        ->where('university_id', $university->id)
        ->where('role_id', Role::query()->where('slug', 'lecturer')->whereNull('university_id')->value('id'))
        ->exists();
}

function hrLogin(object $test, University $university, string $email, string $password): TestResponse
{
    app('auth')->forgetGuards();

    return $test->withHeaders(hrHeaders($university))
        ->postJson('/api/v1/login', ['email' => $email, 'password' => $password]);
}

test('a dosen added by SDM gets a login account and can log in as dosen', function () {
    $create = hrCreateLecturer($this);

    $create->assertApiSuccess(201);
    expect($create->json('message'))->toBe('Dosen dan akun login berhasil ditambahkan.');
    expect($create->json('meta.credentials.email'))->toBe('budi.santoso@kampus.test');
    expect($create->json('meta.credentials.account_created'))->toBeTrue();
    $password = $create->json('meta.credentials.password');
    expect($password)->toBeString()->not->toBeEmpty();

    // Satu akun, tertaut di kedua sisi, dengan role lecturer + keanggotaan dosen.
    $user = User::query()->where('email', 'budi.santoso@kampus.test')->sole();
    $employee = Employee::withoutGlobalScopes()->findOrFail($create->json('data.id'));
    $lecturer = Lecturer::withoutGlobalScopes()->where('employee_id', $employee->id)->sole();
    expect($employee->user_id)->toBe($user->id);
    expect($lecturer->user_id)->toBe($user->id);
    expect($user->must_change_password)->toBeTrue();
    expect(hrHoldsLecturerRole($user, $this->university))->toBeTrue();
    expect(UserUniversity::query()->where('user_id', $user->id)->where('university_id', $this->university->id)->value('membership_type'))
        ->toBe(MembershipType::Lecturer);

    // Login dengan kredensial awal → wajib ganti password.
    $login = hrLogin($this, $this->university, 'budi.santoso@kampus.test', $password);
    $login->assertApiSuccess();
    expect($login->json('data.user.must_change_password'))->toBeTrue();
    $asDosen = fn () => $this->withToken($login->json('data.token'))->withHeaders(hrHeaders($this->university));

    $asDosen()->postJson('/api/v1/change-password', [
        'current_password' => $password,
        'password' => 'PasswordBaru123',
        'password_confirmation' => 'PasswordBaru123',
    ])->assertApiSuccess();

    // Fitur dosen (Akademik + layanan mandiri SDM) terbuka, halaman SDM tidak.
    expect($asDosen()->getJson('/api/v1/me')->json('data.roles'))->toBe(['lecturer']);
    expect($asDosen()->getJson('/api/v1/lecturer-profile')->json('data.id'))->toBe($lecturer->id);
    expect($asDosen()->getJson('/api/v1/me/hr/profile')->json('data.employee.id'))->toBe($employee->id);
    $asDosen()->getJson('/api/v1/hr/employees')->assertApiError(403);
});

test('a dosen without an email is rejected unless the account is skipped', function () {
    hrCreateLecturer($this, ['email' => null])
        ->assertApiError(422)
        ->assertJsonValidationErrors('email');

    $create = hrCreateLecturer($this, ['email' => null, 'create_account' => false]);

    $create->assertApiSuccess(201);
    expect($create->json('meta'))->toBe([]);
    expect(Lecturer::withoutGlobalScopes()->where('employee_id', $create->json('data.id'))->value('user_id'))->toBeNull();
});

test('the email of another dosen cannot be reused', function () {
    hrCreateLecturer($this)->assertApiSuccess(201);

    hrCreateLecturer($this, ['nidn' => '9999999999', 'name' => 'Dosen Lain'])
        ->assertApiError(422)
        ->assertJsonValidationErrors('email');
});

test('SDM can link an existing tenant account instead of creating one', function () {
    $existing = User::factory()->create(['email' => 'lama@kampus.test']);
    hrMembership($existing, $this->university);

    $create = hrCreateLecturer($this, ['email' => null, 'user_id' => $existing->id]);

    $create->assertApiSuccess(201);
    expect($create->json('meta.credentials'))->toBe(['email' => 'lama@kampus.test', 'password' => null, 'account_created' => false]);
    expect(Lecturer::withoutGlobalScopes()->where('employee_id', $create->json('data.id'))->value('user_id'))->toBe($existing->id);
    expect(hrHoldsLecturerRole($existing, $this->university))->toBeTrue();
});

test('staff (tendik) are not given a dosen account', function () {
    $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->postJson('/api/v1/hr/employees', ['employee_type' => 'staff', 'name' => 'Staf', 'employment_status' => 'permanent', 'create_account' => true])
        ->assertApiError(422)
        ->assertJsonValidationErrors('create_account');
});

test('SDM edits keep the dosen login account in sync and never unlink it', function () {
    $create = hrCreateLecturer($this);
    $employeeId = $create->json('data.id');
    $user = User::query()->where('email', 'budi.santoso@kampus.test')->sole();

    $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->putJson("/api/v1/hr/employees/{$employeeId}", [
            'name' => 'Prof. Budi Santoso',
            'email' => 'budi@kampus.test',
            'employment_status' => 'permanent',
            'nidn' => '0123456789',
            'user_id' => null,
        ])
        ->assertApiSuccess();

    expect($user->refresh()->only('name', 'email'))->toBe(['name' => 'Prof. Budi Santoso', 'email' => 'budi@kampus.test']);
    expect(Employee::withoutGlobalScopes()->find($employeeId)->user_id)->toBe($user->id);
});

test('deactivating or deleting the employee blocks the dosen login', function () {
    $create = hrCreateLecturer($this, ['password' => 'Rahasia123']);
    $employeeId = $create->json('data.id');
    $headers = hrHeaders($this->university);

    $this->actingAs($this->sdm)->withHeaders($headers)
        ->patchJson("/api/v1/hr/employees/{$employeeId}/deactivate", ['reason' => 'Resign'])
        ->assertApiSuccess();
    hrLogin($this, $this->university, 'budi.santoso@kampus.test', 'Rahasia123')->assertApiError(403);

    $this->actingAs($this->sdm)->withHeaders($headers)
        ->patchJson("/api/v1/hr/employees/{$employeeId}/activate")
        ->assertApiSuccess();
    hrLogin($this, $this->university, 'budi.santoso@kampus.test', 'Rahasia123')->assertApiSuccess();

    $this->actingAs($this->sdm)->withHeaders($headers)
        ->deleteJson("/api/v1/hr/employees/{$employeeId}")
        ->assertApiSuccess();
    hrLogin($this, $this->university, 'budi.santoso@kampus.test', 'Rahasia123')->assertApiError(403);
});

test('a dosen added from the Dosen menu can also use SDM self-service', function () {
    $create = $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->postJson('/api/v1/lecturers', ['nidn' => '5555', 'name' => 'Dr. Ani', 'email' => 'ani@kampus.test', 'password' => 'Rahasia123']);
    $create->assertApiSuccess(201);

    $lecturer = Lecturer::withoutGlobalScopes()->findOrFail($create->json('data.id'));
    expect(Employee::withoutGlobalScopes()->find($lecturer->employee_id)->user_id)->toBe($lecturer->user_id);

    $token = hrLogin($this, $this->university, 'ani@kampus.test', 'Rahasia123')->json('data.token');
    $this->withToken($token)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/me/hr/profile')
        ->assertApiSuccess();
});
