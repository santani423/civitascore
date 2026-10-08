<?php

use App\Models\User;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Support\LecturerIdentity;

/*
| Tahap 0.4 — satu jalur identitas dosen (LecturerIdentity): lecturers.user_id
| lebih dulu, lalu tautan pegawai employees.user_id → lecturers.employee_id.
| Dipakai pengecekan dosen pengampu, dosen wali, Profil Saya, notifikasi
| KRS, dan pembuatan akun dosen.
*/

/**
 * Dosen yang hanya tertaut lewat lecturers.user_id (tanpa baris pegawai
 * ber-akun) — kebalikan dari portalLecturer().
 *
 * @param  array<string, mixed>  $world
 * @return array{lecturer: Lecturer, user: User}
 */
function identityDirectLecturer(array $world): array
{
    $user = User::factory()->create();
    $lecturer = portalAsTenant($world['university'], fn (): Lecturer => Lecturer::factory()->create([
        'university_id' => $world['university']->id,
        'user_id' => $user->id,
    ]));

    return ['lecturer' => $lecturer, 'user' => $user];
}

test('a user is resolved to their lecturer through either link, and lecturers.user_id always wins', function () {
    $world = portalWorld();
    $direct = identityDirectLecturer($world);
    $viaEmployee = portalLecturer($world);

    $both = portalLecturer($world);
    portalAsTenant($world['university'], fn () => $both['lecturer']->update(['user_id' => $both['user']->id]));

    // Dosen yang sudah tertaut ke akun lain tidak boleh diklaim lewat akun pegawainya.
    $claimed = portalLecturer($world);
    $owner = User::factory()->create();
    portalAsTenant($world['university'], fn () => $claimed['lecturer']->update(['user_id' => $owner->id]));

    $stranger = User::factory()->create();

    portalAsTenant($world['university'], function () use ($world, $direct, $viaEmployee, $both, $claimed, $owner, $stranger): void {
        $identity = app(LecturerIdentity::class);
        $withoutAccount = Lecturer::factory()->create(['university_id' => $world['university']->id]);

        expect($identity->lecturerFor($direct['user'])?->id)->toBe($direct['lecturer']->id)
            ->and($identity->lecturerFor($viaEmployee['user'])?->id)->toBe($viaEmployee['lecturer']->id)
            ->and($identity->lecturerFor($both['user'])?->id)->toBe($both['lecturer']->id)
            ->and($identity->lecturerFor($claimed['user']))->toBeNull()
            ->and($identity->lecturerFor($owner)?->id)->toBe($claimed['lecturer']->id)
            ->and($identity->lecturerFor($stranger))->toBeNull()
            ->and($identity->accountOf($direct['lecturer'])?->id)->toBe($direct['user']->id)
            ->and($identity->accountOf($viaEmployee['lecturer'])?->id)->toBe($viaEmployee['user']->id)
            ->and($identity->accountOf($withoutAccount))->toBeNull();
    });
});

test('a lecturer of another university is never resolved', function () {
    $world = portalWorld();
    $other = portalWorld(actingAs: false);
    $lecturer = identityDirectLecturer($other);

    portalAsTenant($world['university'], fn () => expect(app(LecturerIdentity::class)->lecturerFor($lecturer['user']))->toBeNull());
});

test('a lecturer linked only through lecturers.user_id is recognised as class lecturer and academic advisor', function () {
    $world = portalWorld();
    $lecturer = identityDirectLecturer($world);
    portalGrant($world, $lecturer['user'], ['course_materials.read', 'course_materials.create', 'krs_advising.read']);
    $classSection = portalClass($world, portalCourse($world), [[2, '08:00', '10:30']], ['lecturer_id' => $lecturer['lecturer']->id]);
    portalAsTenant($world['university'], fn () => $world['student']->update(['academic_advisor_id' => $lecturer['lecturer']->id]));

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])->assertApiSuccess(201);
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/submit')->assertApiSuccess();

    expect($lecturer['user']->notifications()->where('data->event_key', 'student.krs_review_requested')->exists())->toBeTrue();

    $this->actingAs($lecturer['user']);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/teaching/classes')
        ->assertApiSuccess()
        ->assertJsonPath('data.0.id', $classSection->id);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/class-sections/{$classSection->id}/materials", [
        'title' => 'Pengantar', 'type' => 'text', 'description' => 'Isi materi',
    ])->assertApiSuccess(201);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/krs-submissions')
        ->assertApiSuccess()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.student.nim', $world['student']->nim);
});

test('a lecturer linked only through their employee record can open their own profile', function () {
    $world = portalWorld();
    $lecturer = portalLecturer($world);
    portalGrant($world, $lecturer['user'], ['lecturer_profile.read']);

    $this->actingAs($lecturer['user']);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/lecturer-profile')
        ->assertApiSuccess()
        ->assertJsonPath('data.id', $lecturer['lecturer']->id);
});

test('creating an account for a lecturer whose employee record already has a login links that login', function () {
    $world = portalWorld();
    $lecturer = portalLecturer($world);
    actingAsUserWithUniversityPermissions($world['university'], ['lecturers.update']);
    $userCount = User::query()->count();

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/lecturers/{$lecturer['lecturer']->id}/account")
        ->assertApiSuccess(201)
        ->assertJsonPath('meta.credentials.account_created', false)
        ->assertJsonPath('meta.credentials.email', $lecturer['user']->email)
        ->assertJsonPath('meta.credentials.password', null);

    expect(User::query()->count())->toBe($userCount)
        ->and($lecturer['lecturer']->refresh()->user_id)->toBe($lecturer['user']->id);

    // Kedua kalinya ditolak — tidak pernah ada akun kedua.
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/lecturers/{$lecturer['lecturer']->id}/account")
        ->assertApiError(409);
});
