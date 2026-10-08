<?php

use App\Models\User;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Models\CoursePrerequisite;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Models\ActivityLog;
use Modules\AuditLog\Models\AuditLog;
use Modules\Tenancy\Enums\MembershipType;
use Modules\UserManagement\Database\Seeders\OrganizationalRoleSeeder;
use Modules\UserManagement\Database\Seeders\RolePermissionSeeder;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/*
| Pengaturan data akademik oleh Bagian Akademik yang dikonsumsi Portal
| Mahasiswa: jadwal & dosen kelas (deteksi bentrok), prasyarat, dosen wali,
| periode KRS, kalender akademik.
*/

test('the academic office sets a class schedule and lecturer, with lecturer and room clash detection', function () {
    $world = portalWorld();
    $lecturer = portalLecturer($world)['lecturer'];
    $busyClass = portalClass($world, portalCourse($world, ['name' => 'Statistika']), [[1, '08:00', '10:00', 'R.201']], ['lecturer_id' => $lecturer->id, 'class_code' => 'A']);
    $classSection = portalClass($world, portalCourse($world, ['name' => 'Basis Data']));

    actingAsUserWithUniversityPermissions($world['university'], ['classes.update']);

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/class-sections/{$classSection->id}/teaching", [
        'lecturer_id' => $lecturer->id,
        'schedules' => [['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => 'R.305']],
    ])->assertApiError(409)
        ->assertJsonPath('message', "Jadwal bentrok dengan kelas Statistika A yang juga diampu {$lecturer->name} (Senin 08:00–10:00 (R.201)).");

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/class-sections/{$classSection->id}/teaching", [
        'lecturer_id' => null,
        'schedules' => [['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => 'r.201']],
    ])->assertApiError(409);

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/class-sections/{$classSection->id}/teaching", [
        'lecturer_id' => $lecturer->id,
        'schedules' => [['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => 'R.201']],
    ])->assertApiSuccess()
        ->assertJsonPath('data.lecturer.id', $lecturer->id)
        ->assertJsonPath('data.schedules.0.day_label', 'Selasa');

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/class-sections/{$classSection->id}/teaching", [
        'schedules' => [['day_of_week' => 2, 'start_time' => '11:00', 'end_time' => '09:00']],
    ])->assertApiError(422);

    expect($busyClass)->not->toBeNull();
});

test('a lecturer clash returns SCHEDULE_CONFLICT and can be forced with a reason that is audited', function () {
    $world = portalWorld();
    $lecturer = portalLecturer($world)['lecturer'];
    portalClass($world, portalCourse($world, ['name' => 'Statistika']), [[1, '08:00', '10:00', 'R.201']], ['lecturer_id' => $lecturer->id, 'class_code' => 'A']);
    $classSection = portalClass($world, portalCourse($world, ['name' => 'Basis Data']));
    actingAsUserWithUniversityPermissions($world['university'], ['classes.update']);

    $url = "/api/v1/class-sections/{$classSection->id}/teaching";
    $payload = ['lecturer_id' => $lecturer->id, 'schedules' => [['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => 'R.305']]];

    $this->withHeaders(portalHeaders($world))->putJson($url, $payload)
        ->assertApiError(409)
        ->assertJsonPath('errors.code', 'SCHEDULE_CONFLICT')
        ->assertJsonPath('errors.conflicts.0.type', 'lecturer')
        ->assertJsonPath('errors.conflicts.0.forceable', true)
        ->assertJsonPath('errors.conflicts.0.class_code', 'A');

    $this->withHeaders(portalHeaders($world))->putJson($url, [...$payload, 'force' => true])
        ->assertApiError(422)
        ->assertJsonPath('errors.reason.0', 'Alasan wajib diisi untuk memaksa jadwal yang bentrok.');
    $this->withHeaders(portalHeaders($world))->putJson($url, [...$payload, 'force' => true, 'reason' => 'singkat'])
        ->assertApiError(422)
        ->assertJsonPath('errors.reason.0', 'Alasan minimal 10 karakter.');

    $reason = 'Team teaching bergantian dengan dosen tamu.';
    $this->withHeaders(portalHeaders($world))->putJson($url, [...$payload, 'force' => true, 'reason' => $reason])
        ->assertApiSuccess()
        ->assertJsonPath('data.lecturer.id', $lecturer->id)
        ->assertJsonPath('meta.overridden_conflicts.0.type', 'lecturer');

    $audit = AuditLog::query()->where('auditable_id', $classSection->id)->where('action', AuditAction::ForcedOverride)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->reason)->toBe($reason)
        ->and($audit->new_values['conflicts'][0]['class_code'])->toBe('A')
        ->and(AuditLog::query()->where('auditable_id', $classSection->id)->where('action', AuditAction::Updated)->value('reason'))->toBe($reason)
        ->and(ActivityLog::query()->where('subject_id', $classSection->id)->pluck('properties')->pluck('event')->all())
        ->toEqualCanonicalizing(['LECTURER_ASSIGNED', 'CLASS_SCHEDULE_CHANGED', 'SCHEDULE_CONFLICT_OVERRIDDEN']);
});

test('a room clash can never be forced, while empty and online rooms never clash', function () {
    $world = portalWorld();
    portalClass($world, portalCourse($world, ['name' => 'Statistika']), [[1, '08:00', '10:00', 'R.201'], [2, '08:00', '10:00', 'Online']], ['class_code' => 'A']);
    $classSection = portalClass($world, portalCourse($world, ['name' => 'Basis Data']));
    actingAsUserWithUniversityPermissions($world['university'], ['classes.update']);
    $url = "/api/v1/class-sections/{$classSection->id}/teaching";

    // Ruangan dinormalkan: spasi & huruf besar/kecil tidak membuatnya lolos.
    $this->withHeaders(portalHeaders($world))->putJson($url, [
        'schedules' => [['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => '  r.201 ']],
        'force' => true,
        'reason' => 'Mencoba memaksa ruangan yang sama.',
    ])->assertApiError(409)
        ->assertJsonPath('errors.code', 'SCHEDULE_CONFLICT')
        ->assertJsonPath('errors.conflicts.0.type', 'room')
        ->assertJsonPath('errors.conflicts.0.forceable', false);

    $this->withHeaders(portalHeaders($world))->putJson($url, [
        'schedules' => [
            ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => 'online'],
            ['day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => '  Lab   Komputer '],
        ],
    ])->assertApiSuccess()
        ->assertJsonPath('data.schedules.1.room', 'Lab Komputer')
        ->assertJsonPath('meta.overridden_conflicts', []);

    $this->withHeaders(portalHeaders($world))->putJson($url, [
        'schedules' => [
            ['day_of_week' => 4, 'start_time' => '09:00', 'end_time' => '11:00'],
            ['day_of_week' => 4, 'start_time' => '10:00', 'end_time' => '12:00'],
        ],
        'force' => true,
        'reason' => 'Dua jadwal di hari yang sama.',
    ])->assertApiError(409)
        ->assertJsonPath('message', 'Jadwal ke-1 dan ke-2 pada kelas ini saling bentrok (Kamis 10:00–12:00).')
        ->assertJsonPath('errors.conflicts.0.type', 'internal')
        ->assertJsonPath('errors.conflicts.0.row', 0)
        ->assertJsonPath('errors.conflicts.0.other_row', 1)
        ->assertJsonPath('errors.conflicts.0.class_section_id', null);
});

test('each conflict points at the submitted row and the clashing slot, and inactive classes never clash', function () {
    $world = portalWorld();
    $lecturer = portalLecturer($world)['lecturer'];
    $busy = portalClass($world, portalCourse($world, ['name' => 'Statistika']), [[1, '08:00', '10:00', 'R.201']], ['lecturer_id' => $lecturer->id, 'class_code' => 'A']);
    // Kelas nonaktif (mis. batal) tidak memakai dosen maupun ruangan.
    portalClass($world, portalCourse($world, ['name' => 'Etika Profesi']), [[2, '08:00', '10:00', 'R.201']], ['lecturer_id' => $lecturer->id, 'class_code' => 'X', 'is_active' => false]);
    // Periode lain tidak pernah dibandingkan — tanggal term tidak beririsan.
    portalClass($world, portalCourse($world, ['name' => 'Kalkulus']), [[3, '08:00', '10:00', 'R.201']], ['lecturer_id' => $lecturer->id, 'class_code' => 'L'], portalPastTerm($world, '2025/2026', AcademicSemester::Genap, now()->subMonths(7)->toDateString()));
    $classSection = portalClass($world, portalCourse($world, ['name' => 'Basis Data']));
    actingAsUserWithUniversityPermissions($world['university'], ['classes.update']);
    $url = "/api/v1/class-sections/{$classSection->id}/teaching";

    $this->withHeaders(portalHeaders($world))->putJson($url, [
        'lecturer_id' => $lecturer->id,
        'schedules' => [
            ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '10:00', 'room' => 'R.201'],
            ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '10:00', 'room' => 'R.201'],
            ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => 'r.201'],
        ],
    ])->assertApiError(409)
        ->assertJsonPath('errors.code', 'SCHEDULE_CONFLICT')
        ->assertJsonCount(2, 'errors.conflicts')
        ->assertJsonPath('errors.conflicts.0.type', 'lecturer')
        ->assertJsonPath('errors.conflicts.1.type', 'room')
        ->assertJsonPath('errors.conflicts.1.row', 2)
        ->assertJsonPath('errors.conflicts.1.other_row', null)
        ->assertJsonPath('errors.conflicts.1.class_section_id', $busy->id)
        ->assertJsonPath('errors.conflicts.1.course_name', 'Statistika')
        ->assertJsonPath('errors.conflicts.1.day_of_week', 1)
        ->assertJsonPath('errors.conflicts.1.start_time', '08:00')
        ->assertJsonPath('errors.conflicts.1.end_time', '10:00')
        ->assertJsonPath('errors.conflicts.1.room', 'R.201');

    $this->withHeaders(portalHeaders($world))->putJson($url, [
        'lecturer_id' => $lecturer->id,
        'schedules' => [
            ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '10:00', 'room' => 'R.201'],
            ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '10:00', 'room' => 'R.201'],
        ],
    ])->assertApiSuccess()
        ->assertJsonPath('meta.overridden_conflicts', []);
});

test('changing the schedule of a class with participants warns about their clashing classes', function () {
    $world = portalWorld();
    $other = portalClass($world, portalCourse($world, ['name' => 'Statistika']), [[3, '08:00', '10:00', 'R.201']], ['class_code' => 'A']);
    $classSection = portalClass($world, portalCourse($world, ['name' => 'Basis Data']), [], ['class_code' => 'B']);
    portalEnroll($world, $world['student'], $other);
    portalEnroll($world, $world['student'], $classSection);
    actingAsUserWithUniversityPermissions($world['university'], ['classes.update']);
    $url = "/api/v1/class-sections/{$classSection->id}/teaching";

    // Dua slot baru sama-sama beririsan dengan satu slot kelas A → satu
    // peringatan, bukan dua.
    $schedules = [
        ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '09:00', 'room' => 'R.305'],
        ['day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => 'R.305'],
    ];

    $this->withHeaders(portalHeaders($world))->putJson($url, ['schedules' => $schedules])
        ->assertApiSuccess()
        ->assertJsonCount(1, 'meta.warnings.student_conflicts')
        ->assertJsonPath('meta.warnings.student_conflicts.0.nim', $world['student']->nim)
        ->assertJsonPath('meta.warnings.student_conflicts.0.class_section_id', $other->id)
        ->assertJsonPath('meta.warnings.student_conflicts.0.class_code', 'A')
        ->assertJsonPath('meta.warnings.student_conflicts.0.schedule', 'Rabu 08:00–10:00 (R.201)');

    // Hanya dosen yang berubah → jadwal tetap, tidak ada peringatan baru.
    $this->withHeaders(portalHeaders($world))->putJson($url, ['lecturer_id' => portalLecturer($world)['lecturer']->id, 'schedules' => $schedules])
        ->assertApiSuccess()
        ->assertJsonPath('meta.warnings.student_conflicts', []);
});

test('the academic office finds classes without a lecturer and assigns one, using the real role', function () {
    $this->seed([RolePermissionSeeder::class, OrganizationalRoleSeeder::class]);

    $world = portalWorld();
    $lecturer = portalLecturer($world)['lecturer'];
    $assigned = portalClass($world, portalCourse($world, ['name' => 'Statistika']), [[1, '08:00', '10:00', 'R.201']], ['lecturer_id' => $lecturer->id, 'class_code' => 'A']);
    $unassigned = portalClass($world, portalCourse($world, ['name' => 'Basis Data']), [], ['class_code' => 'B']);

    $officer = User::factory()->create();
    portalGrant($world, $officer, [], MembershipType::Staff);
    UserRole::query()->create([
        'user_id' => $officer->id,
        'role_id' => Role::query()->where('slug', 'academic_administrator')->whereNull('university_id')->value('id'),
        'university_id' => $world['university']->id,
        'assigned_at' => now(),
    ]);
    $this->actingAs($officer);

    // Pilihan dosen pengampu di panel "Dosen & Jadwal".
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/lecturers')
        ->assertApiSuccess()
        ->assertJsonPath('data.0.id', $lecturer->id);

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/class-sections?filter[has_lecturer]=0')
        ->assertApiSuccess()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $unassigned->id)
        ->assertJsonPath('data.0.lecturer', null)
        ->assertJsonPath('data.0.schedules', []);

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/class-sections?filter[has_lecturer]=1')
        ->assertApiSuccess()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $assigned->id)
        ->assertJsonPath('data.0.lecturer.name', $lecturer->name)
        ->assertJsonPath('data.0.schedules.0.day_label', 'Senin');

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/class-sections/{$unassigned->id}/teaching", [
        'lecturer_id' => $lecturer->id,
        'schedules' => [['day_of_week' => 2, 'start_time' => '13:00', 'end_time' => '15:30', 'room' => 'Lab 1']],
    ])->assertApiSuccess();

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/class-sections?filter[has_lecturer]=0')
        ->assertJsonPath('meta.total', 0);

    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/class-sections/{$unassigned->id}")
        ->assertApiSuccess()
        ->assertJsonPath('data.lecturer_id', $lecturer->id)
        ->assertJsonPath('data.lecturer.id', $lecturer->id)
        ->assertJsonPath('data.schedules.0.start_time', '13:00')
        ->assertJsonPath('data.schedules.0.room', 'Lab 1');
});

test('students cannot use academic administration endpoints', function () {
    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world));
    $course = portalCourse($world);

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/class-sections/{$classSection->id}/teaching", ['schedules' => []])->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/courses/{$course->id}/prerequisites", ['prerequisites' => []])->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->patchJson("/api/v1/students/{$world['student']->id}/academic-advisor", ['lecturer_id' => null])->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->patchJson("/api/v1/academic-terms/{$world['term']->id}/krs-period", [])->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/academic-calendar-events', [])->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/krs-items')->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/grades')->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/students')->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/krs-items', ['student_id' => $world['student']->id, 'class_section_id' => $classSection->id])->assertApiError(403);
    $ownItem = portalEnroll($world, $world['student'], $classSection);
    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/krs-items/{$ownItem->id}/grade", ['score' => 100])->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->patchJson("/api/v1/krs-items/{$ownItem->id}/drop")->assertApiError(403);
});

test('prerequisites reject self-references and cycles', function () {
    $world = portalWorld();
    $basic = portalCourse($world, ['name' => 'Algoritma']);
    $advanced = portalCourse($world, ['name' => 'Struktur Data']);

    actingAsUserWithUniversityPermissions($world['university'], ['courses.update', 'courses.read']);

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/courses/{$advanced->id}/prerequisites", [
        'prerequisites' => [['course_id' => $basic->id, 'min_letter_grade' => 'B']],
    ])->assertApiSuccess()
        ->assertJsonPath('data.0.min_letter_grade', 'B');

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/courses/{$basic->id}/prerequisites", [
        'prerequisites' => [['course_id' => $advanced->id]],
    ])->assertApiError(422);

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/courses/{$basic->id}/prerequisites", [
        'prerequisites' => [['course_id' => $basic->id]],
    ])->assertApiError(422);

    expect(CoursePrerequisite::query()->withoutGlobalScopes()->count())->toBe(1);
});

test('the academic office assigns an academic advisor, sets the KRS period and manages calendar events', function () {
    $world = portalWorld();
    $lecturer = portalLecturer($world)['lecturer'];

    actingAsUserWithUniversityPermissions($world['university'], ['students.update', 'krs.update', 'academic_calendar.read', 'academic_calendar.create', 'academic_calendar.update', 'academic_calendar.delete']);

    $this->withHeaders(portalHeaders($world))->patchJson("/api/v1/students/{$world['student']->id}/academic-advisor", ['lecturer_id' => $lecturer->id])
        ->assertApiSuccess()
        ->assertJsonPath('data.academic_advisor.id', $lecturer->id);
    expect($world['student']->fresh()->academic_advisor_id)->toBe($lecturer->id);

    $this->withHeaders(portalHeaders($world))->patchJson("/api/v1/academic-terms/{$world['term']->id}/krs-period", [
        'krs_start_date' => '2026-10-10', 'krs_end_date' => '2026-10-01',
    ])->assertApiError(422);

    $this->withHeaders(portalHeaders($world))->patchJson("/api/v1/academic-terms/{$world['term']->id}/krs-period", [
        'krs_start_date' => '2026-10-01', 'krs_end_date' => '2026-10-15',
    ])->assertApiSuccess()
        ->assertJsonPath('data.krs_end_date', '2026-10-15');

    $event = $this->withHeaders(portalHeaders($world))->postJson('/api/v1/academic-calendar-events', [
        'title' => 'Ujian Akhir Semester', 'category' => 'final_exam', 'start_date' => '2026-12-14', 'end_date' => '2026-12-19',
    ])->assertApiSuccess(201)->json('data.id');

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/academic-calendar-events/{$event}", ['title' => 'UAS Ganjil'])
        ->assertJsonPath('data.title', 'UAS Ganjil');
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/academic-calendar-events')->assertJsonCount(1, 'data');
    $this->withHeaders(portalHeaders($world))->deleteJson("/api/v1/academic-calendar-events/{$event}")->assertApiSuccess();
});
