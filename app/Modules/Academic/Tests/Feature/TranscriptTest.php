<?php

use App\Support\Tenancy\TenantContext;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

function enrollAndGrade(University $university, StudyProgram $program, Student $student, AcademicTerm $term, int $credits, LetterGrade $letterGrade, KrsItemStatus $status = KrsItemStatus::Enrolled): void
{
    $course = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'credits' => $credits]);
    $classSection = ClassSection::factory()->create([
        'university_id' => $university->id, 'study_program_id' => $program->id,
        'academic_term_id' => $term->id, 'course_id' => $course->id,
    ]);
    $krsItem = KrsItem::factory()->create([
        'university_id' => $university->id, 'student_id' => $student->id,
        'class_section_id' => $classSection->id, 'academic_term_id' => $term->id,
        'status' => $status,
    ]);
    Grade::factory()->create(['university_id' => $university->id, 'krs_item_id' => $krsItem->id, 'letter_grade' => $letterGrade]);
}

test('the transcript endpoint computes per-term IP and cumulative IPK correctly', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);

    $term1 = AcademicTerm::factory()->create(['university_id' => $university->id, 'academic_year' => '2024/2025', 'start_date' => now()->subMonths(12)]);
    $term2 = AcademicTerm::factory()->create(['university_id' => $university->id, 'academic_year' => '2025/2026', 'start_date' => now()->subMonths(6)]);

    // Term 1: A(3 sks) + B(2 sks) -> 12 + 6 = 18 poin / 5 sks = IP 3.6
    enrollAndGrade($university, $program, $student, $term1, 3, LetterGrade::A);
    enrollAndGrade($university, $program, $student, $term1, 2, LetterGrade::B);

    // Term 2: BC(4 sks) -> 10 poin / 4 sks = IP 2.5
    enrollAndGrade($university, $program, $student, $term2, 4, LetterGrade::BC);

    // Kelas yang sudah di-drop tidak boleh ikut dihitung meskipun (secara data lama) punya nilai.
    enrollAndGrade($university, $program, $student, $term2, 100, LetterGrade::E, KrsItemStatus::Dropped);

    app(TenantContext::class)->setUniversityId(null);

    actingAsUserWithUniversityPermissions($university, ['students.read']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/students/{$student->id}/transcript")
        ->assertApiSuccess();

    expect($response->json('data.total_sks'))->toBe(9);
    expect($response->json('data.ipk'))->toBe(3.11);
    expect($response->json('data.terms'))->toHaveCount(2);

    $terms = collect($response->json('data.terms'))->keyBy('academic_term_id');
    expect($terms[$term1->id]['ip'])->toBe(3.6);
    expect($terms[$term1->id]['sks'])->toBe(5);
    expect($terms[$term2->id]['ip'])->toBe(2.5);
    expect($terms[$term2->id]['sks'])->toBe(4);
});

test('viewing a transcript without permission is rejected', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);
    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    app(TenantContext::class)->setUniversityId(null);

    actingAsUserWithUniversityPermissions($university, []);

    $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/students/{$student->id}/transcript")
        ->assertApiError(403);
});
