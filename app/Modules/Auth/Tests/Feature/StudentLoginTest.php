<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Auth\Enums\LoginHistoryStatus;
use Modules\Auth\Models\LoginHistory;
use Modules\Tenancy\Models\University;

/**
 * Creates an active Student linked to a User whose password is the hashed
 * birth date (DDMMYYYY), the way StudentUserAccountSeeder now sets it up.
 */
function makeStudentWithDefaultPassword(University $university, array $studentOverrides = []): Student
{
    app(TenantContext::class)->setUniversityId($university->id);

    $faculty = Faculty::factory()->create(['university_id' => $university->id]);
    $program = StudyProgram::factory()->create(['university_id' => $university->id, 'faculty_id' => $faculty->id]);

    $student = Student::factory()->create(array_merge([
        'university_id' => $university->id,
        'study_program_id' => $program->id,
        'nim' => '202600123',
        'tanggal_lahir' => '2004-08-15',
        'status' => StudentStatus::Active,
    ], $studentOverrides));

    $defaultPassword = $student->tanggal_lahir->format('dmY');

    $user = User::factory()->create([
        'password' => Hash::make($defaultPassword),
        'must_change_password' => true,
        'is_active' => true,
    ]);

    $student->update(['user_id' => $user->id]);

    app(TenantContext::class)->setUniversityId(null);

    return $student->refresh();
}

test('a student can log in with nim and their birth date as password', function () {
    $university = University::factory()->create();
    $student = makeStudentWithDefaultPassword($university);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/login', ['nim' => $student->nim, 'password' => '15082004']);

    $response->assertApiSuccess();
    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    expect($response->json('data.user.nim'))->toBe($student->nim);
    expect($response->json('data.user.must_change_password'))->toBeTrue();

    expect(LoginHistory::query()->where('user_id', $student->user_id)->where('status', LoginHistoryStatus::Success)->exists())->toBeTrue();
});

test('student login fails with the wrong password', function () {
    $university = University::factory()->create();
    $student = makeStudentWithDefaultPassword($university);

    RateLimiter::clear('login:nim:'.mb_strtolower($student->nim).'|127.0.0.1');

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/login', ['nim' => $student->nim, 'password' => 'wrong-password']);

    $response->assertApiError(422);
    expect($response->json('errors.nim.0'))->toBe('NIM atau password tidak sesuai.');
});

test('student login fails with a nim that does not exist', function () {
    $university = University::factory()->create();

    RateLimiter::clear('login:nim:999999999|127.0.0.1');

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/login', ['nim' => '999999999', 'password' => '15082004']);

    $response->assertApiError(422);
    expect($response->json('errors.nim.0'))->toBe('NIM atau password tidak sesuai.');
});

test('an inactive student cannot log in even with the correct credentials', function () {
    $university = University::factory()->create();
    $student = makeStudentWithDefaultPassword($university, ['status' => StudentStatus::Inactive]);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/login', ['nim' => $student->nim, 'password' => '15082004']);

    $response->assertApiError(403);
});

test('a nim shared by two universities does not let a student log into the other university', function () {
    $universityA = University::factory()->create();
    $universityB = University::factory()->create();

    $studentA = makeStudentWithDefaultPassword($universityA, ['nim' => '202600123', 'tanggal_lahir' => '2004-08-15']);
    makeStudentWithDefaultPassword($universityB, ['nim' => '202600123', 'tanggal_lahir' => '2001-01-01']);

    RateLimiter::clear('login:nim:'.mb_strtolower('202600123').'|127.0.0.1');

    // Correct NIM + password for university A, but scoped to university B's tenant context.
    $response = $this->withHeader('X-University-ID', $universityB->id)
        ->postJson('/api/v1/login', ['nim' => '202600123', 'password' => '15082004']);

    $response->assertApiError(422);

    RateLimiter::clear('login:nim:'.mb_strtolower('202600123').'|127.0.0.1');

    $response = $this->withHeader('X-University-ID', $universityA->id)
        ->postJson('/api/v1/login', ['nim' => '202600123', 'password' => '15082004']);

    $response->assertApiSuccess();
    expect($response->json('data.user.id'))->toBe($studentA->user_id);
});

test('nim login is rejected when no university can be resolved', function () {
    $university = University::factory()->create();
    $student = makeStudentWithDefaultPassword($university);

    RateLimiter::clear('login:nim:'.mb_strtolower($student->nim).'|127.0.0.1');

    $response = $this->postJson('/api/v1/login', ['nim' => $student->nim, 'password' => '15082004']);

    $response->assertApiError(422);
});

test('nim login can fall back to an explicit university_code when the tenant cannot be resolved from the request', function () {
    $university = University::factory()->create();
    $student = makeStudentWithDefaultPassword($university);

    RateLimiter::clear('login:nim:'.mb_strtolower($student->nim).'|127.0.0.1');

    $response = $this->postJson('/api/v1/login', [
        'nim' => $student->nim,
        'password' => '15082004',
        'university_code' => $university->code,
    ]);

    $response->assertApiSuccess();
});

test('email login for other roles keeps working unchanged after adding nim login', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password']);

    $response->assertApiSuccess();
    expect($response->json('data.user.nim'))->toBeNull();
});
