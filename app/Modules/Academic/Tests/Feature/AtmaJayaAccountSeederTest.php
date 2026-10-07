<?php

use App\Models\User;
use Modules\Academic\Database\Seeders\AtmaJayaAccountSeeder;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;

require_once __DIR__.'/../../../HumanResource/Tests/Support/hr_helpers.php';

beforeEach(function () {
    hrSeedRoles();
    $this->uaj = University::factory()->create(['code' => 'UAJ']);
    $this->other = University::factory()->create();
});

test('every atmajaya.com account is linked to Atma Jaya as its default university', function () {
    $staff = User::factory()->create(['email' => 'baru@atmajaya.com']);
    hrMembership($staff, $this->other);
    $outsider = User::factory()->create(['email' => 'orang@lain.test']);

    app(AtmaJayaAccountSeeder::class)->run();

    $membership = UserUniversity::query()->where('user_id', $staff->id)->where('university_id', $this->uaj->id)->first();
    expect($membership)->not->toBeNull()
        ->and($membership->is_default)->toBeTrue()
        ->and($membership->membership_type)->toBe(MembershipType::Staff)
        ->and(UserUniversity::query()->where('user_id', $staff->id)->where('university_id', $this->other->id)->value('is_default'))->toBeFalse()
        ->and(UserUniversity::query()->where('user_id', $outsider->id)->exists())->toBeFalse();
});

test('Atma Jaya sample student accounts move to the campus email domain', function () {
    $user = User::factory()->create(['email' => '12025000001@student.uaj.local']);
    hrMembership($user, $this->uaj, MembershipType::Student);
    $student = hrInTenant($this->uaj, fn () => Student::factory()->create([
        'university_id' => $this->uaj->id, 'nim' => '12025000001', 'email' => null, 'user_id' => $user->id,
    ]));

    app(AtmaJayaAccountSeeder::class)->run();

    expect($student->refresh()->email)->toBe('12025000001@student.atmajaya.com')
        ->and($user->refresh()->email)->toBe('12025000001@student.atmajaya.com');
});

test('lecturer accounts get lecturer records so they appear in the Dosen menu', function () {
    $dosen = hrUserWithRole($this->uaj, 'lecturer', MembershipType::Lecturer);
    $dosen->update(['email' => 'dosen@atmajaya.com']);

    app(AtmaJayaAccountSeeder::class)->run();
    app(AtmaJayaAccountSeeder::class)->run();

    $employee = Employee::query()->withoutGlobalScopes()->where('user_id', $dosen->id)->firstOrFail();
    $lecturers = Lecturer::query()->withoutGlobalScopes()->where('employee_id', $employee->id)->get();

    expect($lecturers)->toHaveCount(1)
        ->and($lecturers->first()->university_id)->toBe($this->uaj->id)
        ->and($lecturers->first()->email)->toBe('dosen@atmajaya.com');

    $sdm = hrUserWithRole($this->uaj, 'hr_administrator');
    $this->actingAs($sdm)->withHeaders(hrHeaders($this->uaj))
        ->getJson('/api/v1/lecturers')
        ->assertApiSuccess()
        ->assertJsonPath('data.0.email', 'dosen@atmajaya.com');
});

test('Bagian SDM can create, update and delete lecturers', function () {
    $sdm = hrUserWithRole($this->uaj, 'hr_administrator');
    $headers = hrHeaders($this->uaj);

    $id = $this->actingAs($sdm)->withHeaders($headers)
        ->postJson('/api/v1/lecturers', ['nidn' => '0011223344', 'name' => 'Dosen Baru', 'is_active' => true])
        ->assertStatus(201)
        ->json('data.id');

    $this->actingAs($sdm)->withHeaders($headers)
        ->putJson("/api/v1/lecturers/{$id}", ['name' => 'Dosen Diubah'])
        ->assertApiSuccess()
        ->assertJsonPath('data.name', 'Dosen Diubah');

    $this->actingAs($sdm)->withHeaders($headers)
        ->deleteJson("/api/v1/lecturers/{$id}")
        ->assertApiSuccess();
});
