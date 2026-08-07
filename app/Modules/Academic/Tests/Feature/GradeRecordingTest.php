<?php

use App\Support\Tenancy\TenantContext;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

function makeGradedKrsItemFixture(University $university): KrsItem
{
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $term = AcademicTerm::factory()->create(['university_id' => $university->id, 'is_current' => true]);
    $course = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $classSection = ClassSection::factory()->create([
        'university_id' => $university->id, 'study_program_id' => $program->id,
        'academic_term_id' => $term->id, 'course_id' => $course->id,
    ]);
    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $krsItem = KrsItem::factory()->create([
        'university_id' => $university->id, 'student_id' => $student->id,
        'class_section_id' => $classSection->id, 'academic_term_id' => $term->id,
        'status' => KrsItemStatus::Enrolled,
    ]);

    app(TenantContext::class)->setUniversityId(null);

    return $krsItem;
}

test('a permitted user can record a score and the letter grade is computed automatically', function () {
    $university = University::factory()->create();
    $krsItem = makeGradedKrsItemFixture($university);

    actingAsUserWithUniversityPermissions($university, ['grades.create']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/krs-items/{$krsItem->id}/grade", ['score' => 90])
        ->assertApiSuccess();

    expect($response->json('data.letter_grade'))->toBe('A');
    expect($response->json('data.score'))->toBe('90.00');
});

test('a manual letter grade overrides the score-derived one', function () {
    $university = University::factory()->create();
    $krsItem = makeGradedKrsItemFixture($university);

    actingAsUserWithUniversityPermissions($university, ['grades.create']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/krs-items/{$krsItem->id}/grade", ['score' => 90, 'letter_grade' => 'B'])
        ->assertApiSuccess();

    expect($response->json('data.letter_grade'))->toBe('B');
});

test('recording a grade without permission is rejected', function () {
    $university = University::factory()->create();
    $krsItem = makeGradedKrsItemFixture($university);

    actingAsUserWithUniversityPermissions($university, []);

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/krs-items/{$krsItem->id}/grade", ['score' => 90])
        ->assertApiError(403);
});

test('resubmitting a grade updates the same row instead of creating a duplicate', function () {
    $university = University::factory()->create();
    $krsItem = makeGradedKrsItemFixture($university);

    actingAsUserWithUniversityPermissions($university, ['grades.create', 'grades.update']);

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/krs-items/{$krsItem->id}/grade", ['score' => 60])
        ->assertApiSuccess();

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/krs-items/{$krsItem->id}/grade", ['score' => 95])
        ->assertApiSuccess()
        ->assertJsonPath('data.letter_grade', 'A');

    $this->assertDatabaseCount('grades', 1);
});

test('grading a dropped enrollment is rejected', function () {
    $university = University::factory()->create();
    $krsItem = makeGradedKrsItemFixture($university);
    $krsItem->update(['status' => KrsItemStatus::Dropped]);

    actingAsUserWithUniversityPermissions($university, ['grades.create']);

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/krs-items/{$krsItem->id}/grade", ['score' => 90])
        ->assertApiError(409);
});
