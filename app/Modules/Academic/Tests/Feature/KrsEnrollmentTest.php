<?php

use App\Support\Tenancy\TenantContext;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

function makeEnrollmentFixture(University $university, array $classSectionOverrides = []): array
{
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $term = AcademicTerm::factory()->create(['university_id' => $university->id, 'is_current' => true, 'start_date' => now()->subMonth()]);
    $course = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'credits' => 3]);
    $classSection = ClassSection::factory()->create(array_merge([
        'university_id' => $university->id,
        'study_program_id' => $program->id,
        'academic_term_id' => $term->id,
        'course_id' => $course->id,
        'capacity' => 30,
    ], $classSectionOverrides));
    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);

    app(TenantContext::class)->setUniversityId(null);

    return compact('program', 'term', 'course', 'classSection', 'student');
}

test('a permitted user can enroll a student into an active class', function () {
    $university = University::factory()->create();
    $fixture = makeEnrollmentFixture($university);

    actingAsUserWithUniversityPermissions($university, ['krs.create']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/krs-items', [
            'student_id' => $fixture['student']->id,
            'class_section_id' => $fixture['classSection']->id,
        ])
        ->assertApiSuccess(201);

    expect($response->json('data.status'))->toBe('enrolled');
    $this->assertDatabaseHas('krs_items', [
        'student_id' => $fixture['student']->id,
        'class_section_id' => $fixture['classSection']->id,
        'status' => 'enrolled',
    ]);
});

test('enrolling without krs.create is rejected', function () {
    $university = University::factory()->create();
    $fixture = makeEnrollmentFixture($university);

    actingAsUserWithUniversityPermissions($university, []);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/krs-items', [
            'student_id' => $fixture['student']->id,
            'class_section_id' => $fixture['classSection']->id,
        ])
        ->assertApiError(403);
});

test('enrolling twice into the same class is rejected', function () {
    $university = University::factory()->create();
    $fixture = makeEnrollmentFixture($university);

    app(TenantContext::class)->setUniversityId($university->id);
    KrsItem::factory()->create([
        'university_id' => $university->id,
        'student_id' => $fixture['student']->id,
        'class_section_id' => $fixture['classSection']->id,
        'academic_term_id' => $fixture['term']->id,
        'status' => KrsItemStatus::Enrolled,
    ]);
    app(TenantContext::class)->setUniversityId(null);

    actingAsUserWithUniversityPermissions($university, ['krs.create']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/krs-items', [
            'student_id' => $fixture['student']->id,
            'class_section_id' => $fixture['classSection']->id,
        ])
        ->assertApiError(409);
});

test('enrolling into a class at full capacity is rejected', function () {
    $university = University::factory()->create();
    $fixture = makeEnrollmentFixture($university, ['capacity' => 1]);

    app(TenantContext::class)->setUniversityId($university->id);
    $otherStudent = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $fixture['program']->id]);
    KrsItem::factory()->create([
        'university_id' => $university->id,
        'student_id' => $otherStudent->id,
        'class_section_id' => $fixture['classSection']->id,
        'academic_term_id' => $fixture['term']->id,
        'status' => KrsItemStatus::Enrolled,
    ]);
    app(TenantContext::class)->setUniversityId(null);

    actingAsUserWithUniversityPermissions($university, ['krs.create']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/krs-items', [
            'student_id' => $fixture['student']->id,
            'class_section_id' => $fixture['classSection']->id,
        ])
        ->assertApiError(409);
});

test('enrolling into an inactive class is rejected', function () {
    $university = University::factory()->create();
    $fixture = makeEnrollmentFixture($university, ['is_active' => false]);

    actingAsUserWithUniversityPermissions($university, ['krs.create']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/krs-items', [
            'student_id' => $fixture['student']->id,
            'class_section_id' => $fixture['classSection']->id,
        ])
        ->assertApiError(409);
});

test('enrolling beyond the SKS limit implied by last term IP is rejected', function () {
    $university = University::factory()->create();

    app(TenantContext::class)->setUniversityId($university->id);
    $program = StudyProgram::factory()->create(['university_id' => $university->id]);

    // Satu kurikulum dipakai bersama untuk semua mata kuliah di bawah —
    // CourseFactory men-generate Curriculum baru per panggilan kalau
    // curriculum_id tidak di-override, dan nama default kurikulum
    // ("Kurikulum {tahun-acak-fake}") sesekali bentrok dengan unique index
    // (university_id, study_program_id, name) kalau dipanggil >1x untuk
    // program studi yang sama, seperti tiga mata kuliah di test ini.
    $curriculum = Curriculum::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);

    // Semester lalu: IP rendah (huruf E) -> batas 18 SKS (lihat §4.10).
    $lastTerm = AcademicTerm::factory()->create(['university_id' => $university->id, 'academic_year' => '2024/2025', 'start_date' => now()->subMonths(8)]);
    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $pastCourse = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'curriculum_id' => $curriculum->id, 'credits' => 3]);
    $pastClassSection = ClassSection::factory()->create([
        'university_id' => $university->id, 'study_program_id' => $program->id,
        'academic_term_id' => $lastTerm->id, 'course_id' => $pastCourse->id,
    ]);
    $pastKrsItem = KrsItem::factory()->create([
        'university_id' => $university->id, 'student_id' => $student->id,
        'class_section_id' => $pastClassSection->id, 'academic_term_id' => $lastTerm->id,
        'status' => KrsItemStatus::Enrolled,
    ]);
    Grade::factory()->create(['university_id' => $university->id, 'krs_item_id' => $pastKrsItem->id, 'letter_grade' => LetterGrade::E, 'score' => 30]);

    // Semester ini: mahasiswa sudah ambil 18 SKS (batas penuh untuk IP rendah), mencoba tambah 3 SKS lagi.
    $currentTerm = AcademicTerm::factory()->create(['university_id' => $university->id, 'academic_year' => '2025/2026', 'is_current' => true, 'start_date' => now()]);
    $fullCourse = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'curriculum_id' => $curriculum->id, 'credits' => 18]);
    $fullClassSection = ClassSection::factory()->create([
        'university_id' => $university->id, 'study_program_id' => $program->id,
        'academic_term_id' => $currentTerm->id, 'course_id' => $fullCourse->id,
    ]);
    KrsItem::factory()->create([
        'university_id' => $university->id, 'student_id' => $student->id,
        'class_section_id' => $fullClassSection->id, 'academic_term_id' => $currentTerm->id,
        'status' => KrsItemStatus::Enrolled,
    ]);

    $extraCourse = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'curriculum_id' => $curriculum->id, 'credits' => 3]);
    $extraClassSection = ClassSection::factory()->create([
        'university_id' => $university->id, 'study_program_id' => $program->id,
        'academic_term_id' => $currentTerm->id, 'course_id' => $extraCourse->id,
    ]);
    app(TenantContext::class)->setUniversityId(null);

    actingAsUserWithUniversityPermissions($university, ['krs.create']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/krs-items', [
            'student_id' => $student->id,
            'class_section_id' => $extraClassSection->id,
        ])
        ->assertApiError(409);
});

test('a permitted user can drop an ungraded enrollment and re-enrolling reuses the same row', function () {
    $university = University::factory()->create();
    $fixture = makeEnrollmentFixture($university);

    app(TenantContext::class)->setUniversityId($university->id);
    $krsItem = KrsItem::factory()->create([
        'university_id' => $university->id,
        'student_id' => $fixture['student']->id,
        'class_section_id' => $fixture['classSection']->id,
        'academic_term_id' => $fixture['term']->id,
        'status' => KrsItemStatus::Enrolled,
    ]);
    app(TenantContext::class)->setUniversityId(null);

    actingAsUserWithUniversityPermissions($university, ['krs.update', 'krs.create']);

    $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/krs-items/{$krsItem->id}/drop")
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'dropped');

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/krs-items', [
            'student_id' => $fixture['student']->id,
            'class_section_id' => $fixture['classSection']->id,
        ])
        ->assertApiSuccess(201);

    expect($response->json('data.id'))->toBe($krsItem->id);
    $this->assertDatabaseCount('krs_items', 1);
});

test('dropping an already-graded enrollment is rejected', function () {
    $university = University::factory()->create();
    $fixture = makeEnrollmentFixture($university);

    app(TenantContext::class)->setUniversityId($university->id);
    $krsItem = KrsItem::factory()->create([
        'university_id' => $university->id,
        'student_id' => $fixture['student']->id,
        'class_section_id' => $fixture['classSection']->id,
        'academic_term_id' => $fixture['term']->id,
        'status' => KrsItemStatus::Enrolled,
    ]);
    Grade::factory()->create(['university_id' => $university->id, 'krs_item_id' => $krsItem->id]);
    app(TenantContext::class)->setUniversityId(null);

    actingAsUserWithUniversityPermissions($university, ['krs.update']);

    $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/krs-items/{$krsItem->id}/drop")
        ->assertApiError(409);
});
