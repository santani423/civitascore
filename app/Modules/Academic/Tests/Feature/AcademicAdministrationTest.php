<?php

use Modules\Academic\Models\CoursePrerequisite;

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
