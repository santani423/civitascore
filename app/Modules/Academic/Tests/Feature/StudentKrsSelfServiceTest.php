<?php

use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\KrsSubmissionStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\CoursePrerequisite;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\KrsSubmission;

/*
| KRS mandiri mahasiswa (RANCANGAN-APLIKASI.md §4.10) — validasi lengkap di
| server: status aktif, periode KRS, kelas tersedia, mata kuliah ganda,
| sudah lulus, prasyarat, batas SKS, kapasitas, bentrok jadwal — plus alur
| draft → submitted → approved/rejected oleh dosen wali.
*/

test('a student sees class offerings with lecturer, schedule, capacity and remaining seats', function () {
    $world = portalWorld();
    $lecturer = portalLecturer($world)['lecturer'];
    $course = portalCourse($world, ['name' => 'Pemrograman Web', 'credits' => 3]);
    $classA = portalClass($world, $course, [[1, '08:00', '10:30', 'Lab 1']], ['class_code' => 'SI-A', 'capacity' => 30, 'lecturer_id' => $lecturer->id]);
    portalClass($world, $course, [[2, '10:00', '12:30']], ['class_code' => 'SI-B']);
    portalEnroll($world, portalOtherStudent($world), $classA);

    $response = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/krs/offerings')->assertApiSuccess();

    $offering = collect($response->json('data.courses'))->firstWhere('course.name', 'Pemrograman Web');
    expect($offering['classes'])->toHaveCount(2);

    $a = collect($offering['classes'])->firstWhere('class_code', 'SI-A');
    expect($a['lecturer']['name'])->toBe($lecturer->name)
        ->and($a['schedules'][0])->toMatchArray(['day_label' => 'Senin', 'start_time' => '08:00', 'end_time' => '10:30', 'room' => 'Lab 1'])
        ->and($a['capacity'])->toBe(30)
        ->and($a['seats_taken'])->toBe(1)
        ->and($a['seats_left'])->toBe(29)
        ->and($a['is_full'])->toBeFalse();
});

test('adding a class creates a draft KRS and updates the credit total', function () {
    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world, ['credits' => 3]), [[1, '08:00', '10:30']]);

    $response = $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])
        ->assertApiSuccess(201);

    expect($response->json('data.status'))->toBe('draft')
        ->and($response->json('data.total_credits'))->toBe(3)
        ->and($response->json('data.max_credits'))->toBe(24)
        ->and($response->json('data.can_submit'))->toBeTrue()
        ->and($response->json('data.items.0.status'))->toBe('draft');

    $item = KrsItem::query()->withoutGlobalScopes()->where('student_id', $world['student']->id)->sole();
    expect($item->status)->toBe(KrsItemStatus::Draft)->and($item->krs_submission_id)->not->toBeNull();
});

test('KRS cannot be changed outside the KRS period', function () {
    $world = portalWorld(termAttributes: ['krs_start_date' => now()->subMonth()->toDateString(), 'krs_end_date' => now()->subWeek()->toDateString()]);
    $classSection = portalClass($world, portalCourse($world));

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])
        ->assertApiError(409)
        ->assertJsonPath('message', fn (string $message) => str_contains($message, 'Periode KRS sudah ditutup'));
});

test('KRS is closed when the academic office has not set a KRS period', function () {
    $world = portalWorld(termAttributes: ['krs_start_date' => null, 'krs_end_date' => null]);

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/krs')
        ->assertApiSuccess()
        ->assertJsonPath('data.can_edit', false)
        ->assertJsonPath('data.notices.0', 'Periode KRS semester ini belum dibuka oleh Bagian Akademik.');
});

test('a student who is not active cannot fill in KRS', function () {
    $world = portalWorld(['status' => StudentStatus::Leave]);
    $classSection = portalClass($world, portalCourse($world));

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])
        ->assertApiError(409)
        ->assertJsonPath('message', fn (string $message) => str_contains($message, 'hanya untuk mahasiswa berstatus Aktif'));
});

test('the same course cannot be taken twice in one semester, even in a different class', function () {
    $world = portalWorld();
    $course = portalCourse($world, ['name' => 'Basis Data']);
    $classA = portalClass($world, $course, [[1, '08:00', '10:30']], ['class_code' => 'A']);
    $classB = portalClass($world, $course, [[3, '08:00', '10:30']], ['class_code' => 'B']);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => $classA->id])->assertApiSuccess(201);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => $classB->id])
        ->assertApiError(409)
        ->assertJsonPath('message', 'Mata kuliah Basis Data sudah ada di KRS Anda (kelas A). Hapus kelas tersebut terlebih dahulu jika ingin pindah kelas.');
});

test('a course already passed cannot be taken again but a failed one can be retaken', function () {
    $world = portalWorld();
    $pastTerm = portalPastTerm($world, '2025/2026', AcademicSemester::Genap, now()->subMonths(7)->toDateString());
    $passed = portalCourse($world, ['name' => 'Pemrograman Web']);
    $failed = portalCourse($world, ['name' => 'Kalkulus']);
    portalGraded($world, $world['student'], $pastTerm, $passed, LetterGrade::B);
    portalGraded($world, $world['student'], $pastTerm, $failed, LetterGrade::D);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => portalClass($world, $passed)->id])
        ->assertApiError(409)
        ->assertJsonPath('message', 'Anda sudah lulus mata kuliah Pemrograman Web dengan nilai B, sehingga tidak dapat mengambilnya kembali.');

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => portalClass($world, $failed)->id])
        ->assertApiSuccess(201);
});

test('a course with an unmet prerequisite is rejected until the prerequisite is passed', function () {
    $world = portalWorld();
    $basic = portalCourse($world, ['code' => 'SI101', 'name' => 'Algoritma Dasar']);
    $advanced = portalCourse($world, ['name' => 'Struktur Data']);
    portalAsTenant($world['university'], fn () => CoursePrerequisite::query()->create([
        'university_id' => $world['university']->id, 'course_id' => $advanced->id, 'prerequisite_course_id' => $basic->id,
    ]));
    $classSection = portalClass($world, $advanced);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])
        ->assertApiError(409)
        ->assertJsonPath('message', 'Anda tidak dapat mengambil mata kuliah ini karena prasyarat belum terpenuhi: SI101 Algoritma Dasar (minimal C).');

    $pastTerm = portalPastTerm($world, '2025/2026', AcademicSemester::Genap, now()->subMonths(7)->toDateString());
    portalGraded($world, $world['student'], $pastTerm, $basic, LetterGrade::BC);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])
        ->assertApiSuccess(201);
});

test('the SKS limit follows the previous semester IP', function () {
    $world = portalWorld();
    $pastTerm = portalPastTerm($world, '2025/2026', AcademicSemester::Genap, now()->subMonths(7)->toDateString());
    // IP semester lalu 1,00 (D) → batas 18 SKS.
    portalGraded($world, $world['student'], $pastTerm, portalCourse($world, ['credits' => 3]), LetterGrade::D);

    $heavy = portalClass($world, portalCourse($world, ['credits' => 16]), [[1, '07:00', '08:00']]);
    $extra = portalClass($world, portalCourse($world, ['credits' => 3]), [[2, '07:00', '08:00']]);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => $heavy->id])
        ->assertApiSuccess(201)
        ->assertJsonPath('data.max_credits', 18);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => $extra->id])
        ->assertApiError(409)
        ->assertJsonPath('message', 'SKS yang dipilih melebihi batas maksimum semester ini (18 SKS). Saat ini Anda mengambil 16 SKS, sedangkan mata kuliah ini 3 SKS.');
});

test('a full class cannot be taken', function () {
    $world = portalWorld();
    $course = portalCourse($world, ['name' => 'Jaringan Komputer']);
    $classSection = portalClass($world, $course, [], ['capacity' => 1, 'class_code' => 'A']);
    portalEnroll($world, portalOtherStudent($world), $classSection, KrsItemStatus::Draft);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])
        ->assertApiError(409)
        ->assertJsonPath('message', 'Kelas Jaringan Komputer A sudah penuh. Silakan pilih kelas lain.');
});

test('a class whose schedule overlaps an existing KRS class is rejected', function () {
    $world = portalWorld();
    $first = portalClass($world, portalCourse($world, ['name' => 'Pemrograman Web']), [[1, '08:00', '10:30', 'Lab 1']]);
    $clashing = portalClass($world, portalCourse($world, ['name' => 'Statistika']), [[1, '10:00', '11:40']]);
    $adjacent = portalClass($world, portalCourse($world, ['name' => 'Etika Profesi']), [[1, '10:30', '12:10']]);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => $first->id])->assertApiSuccess(201);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => $clashing->id])
        ->assertApiError(409)
        ->assertJsonPath('message', 'Jadwal bentrok dengan mata kuliah Pemrograman Web (Senin 08:00–10:30 (Lab 1)).')
        ->assertJsonPath('errors.code', 'SCHEDULE_CONFLICT')
        ->assertJsonPath('errors.conflicts.0.type', 'student')
        ->assertJsonPath('errors.conflicts.0.class_section_id', $first->id)
        ->assertJsonPath('errors.conflicts.0.start_time', '08:00');

    // Bersentuhan di ujung (10:30) bukan bentrok.
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => $adjacent->id])->assertApiSuccess(201);
});

test('classes of another study program or another semester are not available', function () {
    $world = portalWorld();
    $pastTerm = portalPastTerm($world, '2025/2026', AcademicSemester::Genap, now()->subMonths(7)->toDateString());
    $oldClass = portalClass($world, portalCourse($world), [], [], $pastTerm);
    $otherProgram = portalAsTenant($world['university'], fn () => \Modules\Academic\Models\StudyProgram::factory()->create(['university_id' => $world['university']->id]));
    $foreignClass = portalClass($world, portalCourse($world), [], ['study_program_id' => $otherProgram->id]);
    $inactiveClass = portalClass($world, portalCourse($world), [], ['is_active' => false]);

    foreach ([$oldClass, $foreignClass, $inactiveClass] as $classSection) {
        $this->withHeaders(portalHeaders($world))
            ->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])
            ->assertApiError(409)
            ->assertJsonPath('message', 'Kelas ini tidak tersedia untuk KRS Anda pada semester ini.');
    }
});

test('submitting locks the KRS, notifies the academic advisor, and approval enrolls the classes', function () {
    $world = portalWorld();
    $advisor = portalLecturer($world);
    portalAsTenant($world['university'], fn () => $world['student']->update(['academic_advisor_id' => $advisor['lecturer']->id]));
    $classSection = portalClass($world, portalCourse($world), [[2, '08:00', '10:30']]);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])->assertApiSuccess(201);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/submit')
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.can_edit', false)
        ->assertJsonPath('data.items.0.status', 'pending');

    expect($advisor['user']->notifications()->where('data->event_key', 'student.krs_review_requested')->exists())->toBeTrue();

    // Setelah diajukan, KRS tidak bisa ditambah lagi.
    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => portalClass($world, portalCourse($world))->id])
        ->assertApiError(409);

    $submission = KrsSubmission::query()->withoutGlobalScopes()->where('student_id', $world['student']->id)->sole();

    // Dosen wali menyetujui.
    $this->actingAs($advisor['user']);
    portalGrant($world, $advisor['user'], ['krs_advising.read', 'krs_advising.approve']);

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/krs-submissions')
        ->assertApiSuccess()
        ->assertJsonPath('data.0.id', $submission->id);

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/krs-submissions/{$submission->id}/approve", ['note' => 'OK'])
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'approved');

    expect(KrsItem::query()->withoutGlobalScopes()->where('student_id', $world['student']->id)->sole()->status)->toBe(KrsItemStatus::Enrolled)
        ->and($world['user']->notifications()->where('data->event_key', 'student.krs_approved')->exists())->toBeTrue();

    // Mahasiswa melihat KRS disetujui & kelasnya muncul di jadwal.
    $this->actingAs($world['user']);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/krs')->assertJsonPath('data.status', 'approved');
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/schedule')
        ->assertApiSuccess()
        ->assertJsonPath('data.slots.0.class_section_id', $classSection->id);
});

test('a rejected KRS returns to draft with the advisor note and can be revised and resubmitted', function () {
    $world = portalWorld();
    $advisor = portalLecturer($world);
    portalAsTenant($world['university'], fn () => $world['student']->update(['academic_advisor_id' => $advisor['lecturer']->id]));
    $first = portalClass($world, portalCourse($world));
    $second = portalClass($world, portalCourse($world));

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => $first->id]);
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/submit')->assertApiSuccess();
    $submission = KrsSubmission::query()->withoutGlobalScopes()->where('student_id', $world['student']->id)->sole();

    $this->actingAs($advisor['user']);
    portalGrant($world, $advisor['user'], ['krs_advising.read', 'krs_advising.approve']);

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/krs-submissions/{$submission->id}/reject")
        ->assertApiError(422);

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/krs-submissions/{$submission->id}/reject", ['note' => 'Tambahkan mata kuliah wajib semester 5.'])
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'rejected');

    $this->actingAs($world['user']);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/krs')
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.submission.decision_note', 'Tambahkan mata kuliah wajib semester 5.')
        ->assertJsonPath('data.items.0.status', 'draft')
        ->assertJsonPath('data.can_edit', true);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => $second->id])
        ->assertApiSuccess(201)
        ->assertJsonPath('data.status', 'draft');

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/submit')->assertJsonPath('data.status', 'submitted');
});

test('a submitted KRS can be withdrawn while the KRS period is open', function () {
    $world = portalWorld();
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => portalClass($world, portalCourse($world))->id]);
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/submit')->assertApiSuccess();

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/cancel')
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.items.0.status', 'draft');
});

test('submitting an empty KRS is rejected', function () {
    $world = portalWorld();

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/submit')->assertApiError(409);
});

test('only the student\'s own academic advisor (or the academic office) can decide a KRS', function () {
    $world = portalWorld();
    $advisor = portalLecturer($world);
    $otherLecturer = portalLecturer($world);
    portalAsTenant($world['university'], fn () => $world['student']->update(['academic_advisor_id' => $advisor['lecturer']->id]));
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => portalClass($world, portalCourse($world))->id]);
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/submit');
    $submission = KrsSubmission::query()->withoutGlobalScopes()->where('student_id', $world['student']->id)->sole();

    // Mahasiswa sendiri tidak punya permission persetujuan.
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/krs-submissions/{$submission->id}/approve")->assertApiError(403);

    // Dosen lain (bukan dosen wali mahasiswa ini) tidak melihat & tidak bisa memutuskan.
    $this->actingAs($otherLecturer['user']);
    portalGrant($world, $otherLecturer['user'], ['krs_advising.read', 'krs_advising.approve']);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/krs-submissions')->assertApiSuccess()->assertJsonCount(0, 'data');
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/krs-submissions/{$submission->id}/approve")->assertApiError(403);

    // Bagian Akademik boleh memutuskan KRS mahasiswa mana pun.
    $admin = actingAsUserWithUniversityPermissions($world['university'], ['krs.approve']);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/krs-submissions/{$submission->id}/approve")
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'approved');

    expect($submission->refresh()->decided_by)->toBe($admin->id);

    // Keputusan tidak bisa diambil dua kali.
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/krs-submissions/{$submission->id}/reject", ['note' => 'Terlambat menolak'])->assertApiError(409);
});

test('a draft class can be removed, but not someone else\'s KRS row', function () {
    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world));
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id]);
    $own = KrsItem::query()->withoutGlobalScopes()->where('student_id', $world['student']->id)->sole();
    $others = portalEnroll($world, portalOtherStudent($world), portalClass($world, portalCourse($world)), KrsItemStatus::Draft);

    $this->withHeaders(portalHeaders($world))->deleteJson("/api/v1/student/krs/items/{$others->id}")->assertApiError(404);
    expect(KrsItem::query()->withoutGlobalScopes()->whereKey($others->id)->exists())->toBeTrue();

    $this->withHeaders(portalHeaders($world))->deleteJson("/api/v1/student/krs/items/{$own->id}")
        ->assertApiSuccess()
        ->assertJsonPath('data.total_credits', 0);
});

test('a KRS fixed by the academic office (no submission) is shown as approved and locked', function () {
    $world = portalWorld();
    portalEnroll($world, $world['student'], portalClass($world, portalCourse($world)));

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/krs')
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.can_edit', false);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/krs/items', ['class_section_id' => portalClass($world, portalCourse($world))->id])
        ->assertApiError(409);
});

test('a logged-in user that is not linked to a student gets a clear 403', function () {
    $world = portalWorld(actingAs: false);
    actingAsUserWithUniversityPermissions($world['university'], portalStudentPermissions());

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/krs')
        ->assertApiError(403)
        ->assertJsonPath('message', 'Akun ini tidak tertaut ke data mahasiswa.');
});

test('the KRS submission status survives approval as the single source of truth', function () {
    $world = portalWorld();
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/items', ['class_section_id' => portalClass($world, portalCourse($world, ['credits' => 4]))->id]);
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/krs/submit');

    $submission = KrsSubmission::query()->withoutGlobalScopes()->where('student_id', $world['student']->id)->sole();
    expect($submission->status)->toBe(KrsSubmissionStatus::Submitted)
        ->and($submission->total_credits)->toBe(4)
        ->and($submission->max_credits)->toBe(24)
        ->and($submission->submitted_at)->not->toBeNull();
});
