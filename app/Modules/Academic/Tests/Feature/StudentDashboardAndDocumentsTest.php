<?php

use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Models\Assignment;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\Exam;
use Modules\Announcement\Models\Announcement;

/*
| Dashboard Portal Mahasiswa (satu respons dari service yang sama dengan
| halaman detail) dan dokumen PDF milik sendiri.
*/

test('the dashboard summarises profile, academics, KRS, exams, assignments, grades and alerts', function () {
    $world = portalWorld(['admission_year' => 2024]);
    $past = portalPastTerm($world, '2025/2026', AcademicSemester::Genap, '2026-02-01');
    $graded = portalGraded($world, $world['student'], $past, portalCourse($world, ['credits' => 3]), LetterGrade::AB);

    $classSection = portalClass($world, portalCourse($world, ['name' => 'Basis Data']), [[now()->dayOfWeekIso, '23:00', '23:50']]);
    $item = portalEnroll($world, $world['student'], $classSection);

    portalAsTenant($world['university'], function () use ($world, $classSection, $item): void {
        Exam::factory()->create([
            'university_id' => $world['university']->id, 'class_section_id' => $classSection->id, 'title' => 'UTS Basis Data',
            'is_published' => true, 'published_at' => now(), 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2),
        ]);
        Assignment::factory()->create([
            'university_id' => $world['university']->id, 'class_section_id' => $classSection->id, 'title' => 'Normalisasi', 'due_at' => now()->addHours(20),
        ]);
        foreach ([AttendanceStatus::Absent, AttendanceStatus::Absent, AttendanceStatus::Present] as $index => $status) {
            Attendance::query()->create([
                'university_id' => $world['university']->id, 'krs_item_id' => $item->id,
                'meeting_number' => $index + 1, 'meeting_date' => now()->subDays(10 - $index)->toDateString(), 'status' => $status,
            ]);
        }
        Announcement::factory()->create(['university_id' => $world['university']->id, 'title' => 'Libur Nasional', 'published_at' => now()->subHour()]);
    });

    $response = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/dashboard')->assertApiSuccess();

    expect($response->json('data.profile.semester'))->toBe(5)
        ->and($response->json('data.academic.ipk'))->toEqual(3.5)
        ->and($response->json('data.krs.status'))->toBe('approved')
        ->and($response->json('data.upcoming_exams.0.title'))->toBe('UTS Basis Data')
        ->and($response->json('data.upcoming_exams.0.status'))->toBe('upcoming')
        ->and($response->json('data.assignments_due.0.title'))->toBe('Normalisasi')
        ->and($response->json('data.announcements.unread_count'))->toBe(1)
        ->and($response->json('data.attendance.below_minimum.0.course_name'))->toBe('Basis Data');

    $alertTypes = collect($response->json('data.alerts'))->pluck('type');
    expect($alertTypes)->toContain('exam')
        ->toContain('assignment')
        ->toContain('attendance')
        ->toContain('announcement');

    expect($graded)->not->toBeNull();
});

test('the dashboard warns when KRS has not been taken during an open KRS period', function () {
    $world = portalWorld();

    $alerts = collect($this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/dashboard')->json('data.alerts'));

    expect($alerts->firstWhere('type', 'krs')['message'])->toBe('KRS semester ini belum diambil.');
});

test('a student downloads their own KRS, KHS and transcript as PDF', function () {
    $world = portalWorld();
    $past = portalPastTerm($world, '2025/2026', AcademicSemester::Genap, '2026-02-01');
    portalGraded($world, $world['student'], $past, portalCourse($world, ['name' => 'Algoritma']), LetterGrade::A);
    portalEnroll($world, $world['student'], portalClass($world, portalCourse($world), [[1, '08:00', '10:00']]));

    $documents = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/documents')->assertApiSuccess();
    expect(collect($documents->json('data'))->pluck('type')->unique()->values()->all())->toBe(['krs', 'khs', 'transcript']);

    foreach ([
        '/api/v1/student/documents/krs',
        "/api/v1/student/documents/khs/{$past->id}",
        '/api/v1/student/documents/transcript',
    ] as $url) {
        $response = $this->withHeaders(portalHeaders($world))->get($url);
        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('application/pdf');
    }

    // KHS semester yang tidak pernah diikuti mahasiswa ini → 404.
    $notTaken = portalPastTerm($world, '2024/2025', AcademicSemester::Genap, '2025-02-01');
    $this->withHeaders(portalHeaders($world))->get("/api/v1/student/documents/khs/{$notTaken->id}", ['Accept' => 'application/json'])->assertApiError(404);
});

test('the transcript PDF is refused while there are no grades yet', function () {
    $world = portalWorld();

    $this->withHeaders(portalHeaders($world))->get('/api/v1/student/documents/transcript', ['Accept' => 'application/json'])
        ->assertApiError(409)
        ->assertJsonPath('message', 'Transkrip belum tersedia karena belum ada nilai.');
});
