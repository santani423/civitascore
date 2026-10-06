<?php

use Carbon\CarbonImmutable;
use Modules\Academic\Enums\AcademicCalendarCategory;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicCalendarEvent;
use Modules\Academic\Models\Assignment;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\Exam;

/*
| Data akademik read-only Portal Mahasiswa. Fokus utama: angka IP/IPK/SKS
| identik di semua halaman (satu sumber: AcademicRecordService) dengan aturan
| pengulangan "nilai terbaik" (RANCANGAN-APLIKASI.md §4.17).
*/

test('IPK uses the best grade of a retaken course and is identical on summary, KHS, grades and transcript', function () {
    $world = portalWorld();
    $term1 = portalPastTerm($world, '2024/2025', AcademicSemester::Ganjil, '2024-08-01');
    $term2 = portalPastTerm($world, '2024/2025', AcademicSemester::Genap, '2025-02-01');

    $algo = portalCourse($world, ['code' => 'SI101', 'name' => 'Algoritma', 'credits' => 3]);
    $math = portalCourse($world, ['code' => 'SI102', 'name' => 'Matematika', 'credits' => 2]);

    // Semester 1: Algoritma E (gagal), Matematika A → IPS (0*3 + 4*2)/5 = 1.60
    portalGraded($world, $world['student'], $term1, $algo, LetterGrade::E);
    portalGraded($world, $world['student'], $term1, $math, LetterGrade::A);
    // Semester 2: Algoritma diulang → B → IPS 3.00
    portalGraded($world, $world['student'], $term2, $algo, LetterGrade::B);

    // IPK nilai terbaik: Algoritma B (3 sks × 3) + Matematika A (2 × 4) = 17 / 5 = 3.40
    $summary = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/academic-summary')->assertApiSuccess();
    expect($summary->json('data.ipk'))->toBe(3.4)
        ->and($summary->json('data.total_credits'))->toBe(5)
        ->and($summary->json('data.passed_credits'))->toBe(5)
        ->and($summary->json('data.last_ips.ips'))->toEqual(3.0);

    $khs = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/khs?academic_term_id='.$term1->id)->assertApiSuccess();
    expect($khs->json('data.summary.ips'))->toBe(1.6)
        // IPK di KHS semester 1 = kumulatif s.d. semester 1 saja.
        ->and($khs->json('data.summary.ipk'))->toBe(1.6)
        ->and($khs->json('data.summary.cumulative_credits'))->toBe(5);

    $latestKhs = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/khs')->assertApiSuccess();
    expect($latestKhs->json('data.term.id'))->toBe($term2->id)
        ->and($latestKhs->json('data.summary.ipk'))->toBe(3.4)
        ->and($latestKhs->json('data.summary.ips'))->toEqual(3.0);

    $transcript = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/transcript')->assertApiSuccess();
    expect($transcript->json('data.summary.ipk'))->toBe(3.4)
        ->and($transcript->json('data.summary.total_credits'))->toBe(5)
        ->and($transcript->json('data.rows'))->toHaveCount(2);

    $algoRow = collect($transcript->json('data.rows'))->firstWhere('course_code', 'SI101');
    expect($algoRow['letter_grade'])->toBe('B')->and($algoRow['attempts'])->toBe(2);

    $grades = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/grades')->assertApiSuccess();
    expect($grades->json('data.ipk'))->toBe(3.4)
        ->and($grades->json('data.terms'))->toHaveCount(2)
        ->and($grades->json('data.terms.0.ips'))->toBe(1.6);

    // Transkrip admin (endpoint lama) memakai sumber yang sama.
    actingAsUserWithUniversityPermissions($world['university'], ['students.read']);
    $admin = $this->withHeaders(portalHeaders($world))->getJson("/api/v1/students/{$world['student']->id}/transcript")->assertApiSuccess();
    expect($admin->json('data.ipk'))->toBe(3.4);
});

test('the academic summary shows semester number, credits, curriculum progress and the academic advisor', function () {
    $world = portalWorld(['admission_year' => 2024]);
    $advisor = portalLecturer($world);
    portalAsTenant($world['university'], fn () => $world['student']->update(['academic_advisor_id' => $advisor['lecturer']->id]));
    $past = portalPastTerm($world, '2025/2026', AcademicSemester::Genap, '2026-02-01');
    portalGraded($world, $world['student'], $past, portalCourse($world, ['credits' => 4]), LetterGrade::A);
    portalCourse($world, ['credits' => 4]);
    portalEnroll($world, $world['student'], portalClass($world, portalCourse($world, ['credits' => 3])));

    $response = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/academic-summary')->assertApiSuccess();

    // Angkatan 2024 pada 2026/2027 Ganjil = semester 5.
    expect($response->json('data.semester'))->toBe(5)
        ->and($response->json('data.status.label'))->toBe('Aktif')
        ->and($response->json('data.study_program.name'))->toBe('Sistem Informasi')
        ->and($response->json('data.academic_advisor.name'))->toBe($advisor['lecturer']->name)
        ->and($response->json('data.current_term_enrolled_credits'))->toBe(3)
        ->and($response->json('data.max_credits'))->toBe(24)
        // Kurikulum: 4 + 4 + 3 = 11 SKS, lulus 4 → sisa 7.
        ->and($response->json('data.curriculum.total_credits'))->toBe(11)
        ->and($response->json('data.remaining_credits'))->toBe(7);
});

test('attendance summary counts each status and flags courses below the minimum', function () {
    $world = portalWorld();
    $item = portalEnroll($world, $world['student'], portalClass($world, portalCourse($world, ['name' => 'Pemrograman Web'])));

    $statuses = [...array_fill(0, 9, AttendanceStatus::Present), AttendanceStatus::Permitted, AttendanceStatus::Sick, AttendanceStatus::Absent];
    portalAsTenant($world['university'], function () use ($world, $item, $statuses): void {
        foreach ($statuses as $index => $status) {
            Attendance::query()->create([
                'university_id' => $world['university']->id, 'krs_item_id' => $item->id,
                'meeting_number' => $index + 1, 'meeting_date' => now()->subDays(30 - $index)->toDateString(), 'status' => $status,
            ]);
        }
    });

    $response = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/attendance')->assertApiSuccess();

    expect($response->json('data.courses.0'))->toMatchArray([
        'course_name' => 'Pemrograman Web', 'total_meetings' => 12, 'present' => 9, 'permitted' => 1, 'sick' => 1, 'absent' => 1,
        'percentage' => 75, 'is_below_minimum' => false,
    ])->and($response->json('data.minimum_percent'))->toEqual(75);

    $detail = $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/attendance/{$item->id}")->assertApiSuccess();
    expect($detail->json('data.meetings'))->toHaveCount(12)
        ->and($detail->json('data.meetings.11.status_label'))->toBe('Alpa');
});

test('a student cannot read another student\'s attendance detail', function () {
    $world = portalWorld();
    $othersItem = portalEnroll($world, portalOtherStudent($world), portalClass($world, portalCourse($world)));

    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/attendance/{$othersItem->id}")->assertApiError(404);
});

test('today\'s schedule marks classes as finished, ongoing or upcoming in the university timezone', function () {
    // Senin 5 Oktober 2026 pukul 10:15 WIB.
    $this->travelTo(CarbonImmutable::parse('2026-10-05 10:15', 'Asia/Jakarta'));

    $world = portalWorld(termAttributes: ['start_date' => '2026-08-01', 'end_date' => '2027-01-31']);
    $finished = portalClass($world, portalCourse($world, ['name' => 'Statistika']), [[1, '07:00', '08:40']]);
    $ongoing = portalClass($world, portalCourse($world, ['name' => 'Basis Data']), [[1, '09:00', '11:00']]);
    $upcoming = portalClass($world, portalCourse($world, ['name' => 'Pemrograman Web']), [[1, '13:00', '15:30']]);
    $tomorrow = portalClass($world, portalCourse($world, ['name' => 'Jaringan']), [[2, '08:00', '10:00']]);
    foreach ([$finished, $ongoing, $upcoming, $tomorrow] as $classSection) {
        portalEnroll($world, $world['student'], $classSection);
    }

    $response = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/schedule')->assertApiSuccess();

    $today = collect($response->json('data.today'))->keyBy('course_name');
    expect($today)->toHaveCount(3)
        ->and($today['Statistika']['status'])->toBe('finished')
        ->and($today['Basis Data']['status'])->toBe('ongoing')
        ->and($today['Pemrograman Web']['status'])->toBe('upcoming')
        ->and($response->json('data.next_class.course_name'))->toBe('Pemrograman Web')
        ->and($response->json('data.slots'))->toHaveCount(4);
});

test('the calendar merges academic events, KRS period, class meetings, exams and assignment deadlines', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00', 'Asia/Jakarta'));

    $world = portalWorld(termAttributes: [
        'start_date' => '2026-08-01', 'end_date' => '2027-01-31',
        'krs_start_date' => '2026-10-01', 'krs_end_date' => '2026-10-10',
    ]);
    $classSection = portalClass($world, portalCourse($world, ['name' => 'Basis Data']), [[3, '08:00', '10:00', 'R.201']]);
    portalEnroll($world, $world['student'], $classSection);

    portalAsTenant($world['university'], function () use ($world, $classSection): void {
        AcademicCalendarEvent::factory()->create([
            'university_id' => $world['university']->id, 'title' => 'Ujian Tengah Semester',
            'category' => AcademicCalendarCategory::MidtermExam, 'start_date' => '2026-10-19', 'end_date' => '2026-10-24',
        ]);
        Exam::factory()->create([
            'university_id' => $world['university']->id, 'class_section_id' => $classSection->id, 'title' => 'Kuis Basis Data',
            'is_published' => true, 'published_at' => now(), 'starts_at' => CarbonImmutable::parse('2026-10-14 01:00'), 'ends_at' => CarbonImmutable::parse('2026-10-14 03:00'),
        ]);
        Assignment::factory()->create([
            'university_id' => $world['university']->id, 'class_section_id' => $classSection->id, 'title' => 'ERD Toko Online',
            'due_at' => CarbonImmutable::parse('2026-10-16 16:59'),
        ]);
    });

    $response = $this->withHeaders(portalHeaders($world))
        ->getJson('/api/v1/student/calendar?from=2026-10-01&to=2026-10-31')
        ->assertApiSuccess();

    $events = collect($response->json('data'));
    expect($events->where('type', 'class'))->toHaveCount(4) // Rabu 7, 14, 21, 28 Oktober
        ->and($events->firstWhere('category', 'krs')['title'])->toBe('Periode KRS 2026/2027 Ganjil')
        ->and($events->firstWhere('category', 'midterm_exam')['title'])->toBe('Ujian Tengah Semester')
        ->and($events->firstWhere('type', 'exam')['title'])->toBe('Kuis Basis Data')
        ->and($events->firstWhere('type', 'assignment')['title'])->toBe('Batas tugas: ERD Toko Online');

    $this->withHeaders(portalHeaders($world))
        ->getJson('/api/v1/student/calendar?from=2026-01-01&to=2026-12-31')
        ->assertApiError(422);
});

test('the academic history timeline includes admission, semesters and status changes', function () {
    $world = portalWorld(['admission_year' => 2024, 'enrolled_at' => '2024-08-01']);
    $term = portalPastTerm($world, '2024/2025', AcademicSemester::Ganjil, '2024-08-15');
    portalGraded($world, $world['student'], $term, portalCourse($world, ['credits' => 3]), LetterGrade::A);

    portalAsTenant($world['university'], function () use ($world): void {
        $student = $world['student']->fresh();
        $student->statusChangeReason = 'Cuti akademik disetujui';
        $student->update(['status' => StudentStatus::Leave]);
    });

    $response = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/academic-history')->assertApiSuccess();

    $types = collect($response->json('data'))->pluck('type');
    expect($types->first())->toBe('admission')
        ->and($types)->toContain('term')
        ->and($types)->toContain('status');

    $status = collect($response->json('data'))->firstWhere('type', 'status');
    expect($status['title'])->toBe('Status menjadi Cuti')
        ->and($status['description'])->toContain('Cuti akademik disetujui');
});

test('a student may update contact fields only; identity fields are ignored', function () {
    $world = portalWorld(['nim' => '2024001', 'name' => 'Budi Santoso']);

    $this->withHeaders(portalHeaders($world))->patchJson('/api/v1/student/profile', [
        'phone' => '+62 812-3456-7890',
        'address' => 'Jl. Sudirman No. 1, Jakarta',
        'nim' => '9999999',
        'name' => 'Nama Palsu',
        'status' => 'graduated',
        'study_program_id' => 'x',
    ])->assertApiSuccess()
        ->assertJsonPath('data.contact.phone', '+62 812-3456-7890')
        ->assertJsonPath('data.address', 'Jl. Sudirman No. 1, Jakarta')
        ->assertJsonPath('data.personal.nim', '2024001')
        ->assertJsonPath('data.personal.name', 'Budi Santoso')
        ->assertJsonPath('data.academic.status.value', 'active');

    $this->withHeaders(portalHeaders($world))->patchJson('/api/v1/student/profile', ['phone' => 'telepon saya'])
        ->assertApiError(422);
});

test('portal endpoints require the student self-service permission', function () {
    $world = portalWorld(actingAs: false);
    $user = actingAsUserWithUniversityPermissions($world['university'], ['exam_participation.read']);
    portalAsTenant($world['university'], fn () => $world['student']->update(['user_id' => $user->id]));

    foreach (['/api/v1/student/profile', '/api/v1/student/krs', '/api/v1/student/transcript', '/api/v1/student/schedule'] as $url) {
        $this->withHeaders(portalHeaders($world))->getJson($url)->assertApiError(403);
    }
});

test('only enrolled KRS rows count toward grades, attendance and schedule — not drafts', function () {
    $world = portalWorld();
    portalEnroll($world, $world['student'], portalClass($world, portalCourse($world, ['name' => 'Draft MK']), [[1, '08:00', '10:00']]), KrsItemStatus::Draft);

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/schedule')->assertJsonCount(0, 'data.slots');
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/attendance')->assertJsonCount(0, 'data.courses');
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/grades')->assertJsonCount(0, 'data.terms');
});
