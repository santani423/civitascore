<?php

use App\Support\Tenancy\TenantContext;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\QuestionSelectionMode;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamQuestion;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

/**
 * @return array{program: StudyProgram, term: AcademicTerm, classSection: ClassSection}
 */
function makeParticipationClassSectionFixture(University $university, ?string $academicYear = null): array
{
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $term = AcademicTerm::factory()->create([
        'university_id' => $university->id,
        'is_current' => true,
        ...($academicYear !== null ? ['academic_year' => $academicYear] : []),
    ]);
    $course = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $classSection = ClassSection::factory()->create([
        'university_id' => $university->id, 'study_program_id' => $program->id,
        'academic_term_id' => $term->id, 'course_id' => $course->id,
    ]);

    app(TenantContext::class)->setUniversityId(null);

    return ['program' => $program, 'term' => $term, 'classSection' => $classSection];
}

/**
 * @return array{question: ExamQuestion, correctOptionId: string, wrongOptionId: string}
 */
function addParticipationQuestionFixture(Exam $exam, int $orderIndex = 0): array
{
    app(TenantContext::class)->setUniversityId($exam->university_id);

    $question = ExamQuestion::factory()->create(['exam_id' => $exam->id, 'order_index' => $orderIndex]);
    $correct = $question->options()->create(['university_id' => $exam->university_id, 'option_text' => "Benar {$orderIndex}", 'is_correct' => true, 'order_index' => 0]);
    $wrong = $question->options()->create(['university_id' => $exam->university_id, 'option_text' => "Salah {$orderIndex}", 'is_correct' => false, 'order_index' => 1]);

    app(TenantContext::class)->setUniversityId(null);

    return ['question' => $question, 'correctOptionId' => $correct->id, 'wrongOptionId' => $wrong->id];
}

/**
 * Sama seperti actingAsUserWithUniversityPermissions(), tapi juga menautkan
 * user yang login ke satu Student baru (identity link `students.user_id`,
 * lihat StudentUserAccountSeeder) dan mendaftarkannya (KrsItem Enrolled) ke
 * kelas yang diberikan — prasyarat untuk endpoint self-service portal
 * mahasiswa yang selalu meresolusi KrsItem dari `$user->student`.
 */
function actingAsEnrolledStudent(University $university, ClassSection $classSection, string $studyProgramId): KrsItem
{
    $user = actingAsUserWithUniversityPermissions($university, [
        'exam_participation.read', 'exam_participation.create', 'exam_participation.update',
    ]);

    app(TenantContext::class)->setUniversityId($university->id);

    $student = Student::factory()->create([
        'university_id' => $university->id,
        'study_program_id' => $studyProgramId,
        'user_id' => $user->id,
    ]);
    $krsItem = KrsItem::factory()->create([
        'university_id' => $university->id,
        'student_id' => $student->id,
        'class_section_id' => $classSection->id,
        'academic_term_id' => $classSection->academic_term_id,
        'status' => KrsItemStatus::Enrolled,
    ]);

    app(TenantContext::class)->setUniversityId(null);

    return $krsItem;
}

test('student only sees published exams for a class they are enrolled in', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);
    $otherFixture = makeParticipationClassSectionFixture($university, '2025/2026');

    $published = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $unpublished = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'is_published' => false,
    ]);
    $notEnrolledExam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $otherFixture['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/student/exams')
        ->assertApiSuccess();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($published->id);
    expect($ids)->not->toContain($unpublished->id);
    expect($ids)->not->toContain($notEnrolledExam->id);
});

test('student cannot start an exam for a class they are not enrolled in', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
    ]);
    addParticipationQuestionFixture($exam, 0);

    // Bukan actingAsEnrolledStudent — user login tidak punya KrsItem sama sekali.
    $user = actingAsUserWithUniversityPermissions($university, ['exam_participation.read', 'exam_participation.create']);
    app(TenantContext::class)->setUniversityId($university->id);
    Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $fixture['program']->id, 'user_id' => $user->id]);
    app(TenantContext::class)->setUniversityId(null);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiError(409);
});

test('student cannot view or answer another students exam attempt', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q = addParticipationQuestionFixture($exam, 0);

    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);
    $start = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiSuccess();
    $attemptId = $start->json('data.id');

    // Mahasiswa lain, terdaftar di kelas yang sama, mencoba mengakses attempt di atas.
    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);

    $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/student/exam-attempts/{$attemptId}")
        ->assertStatus(403);

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/student/exam-attempts/{$attemptId}/answer", [
            'exam_question_id' => $q['question']->id,
            'exam_question_option_id' => $q['correctOptionId'],
        ])
        ->assertStatus(403);

    $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/student/exam-attempts/{$attemptId}/submit")
        ->assertStatus(403);
});

test('student can complete the full exam flow and the answer key is never exposed', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'show_result_after_submission' => false,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q = addParticipationQuestionFixture($exam, 0);

    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);

    $start = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiSuccess();
    $attemptId = $start->json('data.id');

    expect($start->json('data.questions.0'))->not->toHaveKey('is_correct');
    foreach ($start->json('data.questions.0.options') as $option) {
        expect($option)->not->toHaveKey('is_correct');
    }

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/student/exam-attempts/{$attemptId}/answer", [
            'exam_question_id' => $q['question']->id,
            'exam_question_option_id' => $q['correctOptionId'],
        ])
        ->assertApiSuccess();

    $submit = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/student/exam-attempts/{$attemptId}/submit")
        ->assertApiSuccess();

    expect($submit->json('data.status'))->toBe('submitted');
    expect($submit->json('data.result_visible'))->toBeFalse();
    expect($submit->json('data.score'))->toBeNull();

    // Submit ulang bersifat idempotent, tidak menghasilkan attempt/skor ganda.
    $resubmit = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/student/exam-attempts/{$attemptId}/submit")
        ->assertApiSuccess();

    expect($resubmit->json('data.id'))->toBe($attemptId);

    $index = $this->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/student/exams')
        ->assertApiSuccess();

    $examRow = collect($index->json('data'))->firstWhere('id', $exam->id);
    expect($examRow['status'])->toBe('completed');
    expect($examRow['result_visible'])->toBeFalse();
    expect($examRow['score'])->toBeNull();
});

test('exam result is visible once submitted when the lecturer allows it', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'show_result_after_submission' => true,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q = addParticipationQuestionFixture($exam, 0);

    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);

    $start = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiSuccess();
    $attemptId = $start->json('data.id');

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/student/exam-attempts/{$attemptId}/answer", [
            'exam_question_id' => $q['question']->id,
            'exam_question_option_id' => $q['correctOptionId'],
        ])
        ->assertApiSuccess();

    $submit = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/student/exam-attempts/{$attemptId}/submit")
        ->assertApiSuccess();

    expect($submit->json('data.result_visible'))->toBeTrue();
    expect($submit->json('data.score'))->toBe('100.00');
});
