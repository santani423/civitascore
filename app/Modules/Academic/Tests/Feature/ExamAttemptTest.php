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
 * @return array{classSection: ClassSection}
 */
function makeAttemptClassSectionFixture(University $university): array
{
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $term = AcademicTerm::factory()->create(['university_id' => $university->id, 'is_current' => true]);
    $course = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $classSection = ClassSection::factory()->create([
        'university_id' => $university->id, 'study_program_id' => $program->id,
        'academic_term_id' => $term->id, 'course_id' => $course->id,
    ]);

    app(TenantContext::class)->setUniversityId(null);

    return ['program' => $program, 'term' => $term, 'classSection' => $classSection];
}

function enrollStudentFixture(University $university, ClassSection $classSection, string $studyProgramId): KrsItem
{
    app(TenantContext::class)->setUniversityId($university->id);

    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $studyProgramId]);
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

/**
 * @return array{question: ExamQuestion, correctOptionId: string, wrongOptionId: string}
 */
function addAttemptQuestionFixture(Exam $exam, int $orderIndex = 0): array
{
    app(TenantContext::class)->setUniversityId($exam->university_id);

    $question = ExamQuestion::factory()->create(['exam_id' => $exam->id, 'order_index' => $orderIndex]);
    $correct = $question->options()->create(['university_id' => $exam->university_id, 'option_text' => "Benar {$orderIndex}", 'is_correct' => true, 'order_index' => 0]);
    $wrong = $question->options()->create(['university_id' => $exam->university_id, 'option_text' => "Salah {$orderIndex}", 'is_correct' => false, 'order_index' => 1]);

    app(TenantContext::class)->setUniversityId(null);

    return ['question' => $question, 'correctOptionId' => $correct->id, 'wrongOptionId' => $wrong->id];
}

test('starting an attempt twice for the same participant returns the same stable assignment (refresh-safe)', function () {
    $university = University::factory()->create();
    $fixture = makeAttemptClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 3,
        'question_selection_mode' => QuestionSelectionMode::Random,
        'randomize_questions' => true,
        'randomize_options' => true,
        'is_published' => true,
        'published_at' => now(),
    ]);
    for ($i = 0; $i < 5; $i++) {
        addAttemptQuestionFixture($exam, $i);
    }
    $krsItem = enrollStudentFixture($university, $fixture['classSection'], $fixture['program']->id);

    actingAsUserWithUniversityPermissions($university, ['exam_attempts.create']);

    $first = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/krs-items/{$krsItem->id}/exams/{$exam->id}/attempt")
        ->assertApiSuccess();

    $second = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/krs-items/{$krsItem->id}/exams/{$exam->id}/attempt")
        ->assertApiSuccess();

    expect($first->json('data.id'))->toBe($second->json('data.id'));
    expect(collect($first->json('data.questions'))->pluck('id')->all())
        ->toBe(collect($second->json('data.questions'))->pluck('id')->all());
    expect($first->json('data.questions'))->toHaveCount(3);
});

test('random selection assigns a valid subset of the pool sized to questions_per_participant', function () {
    $university = University::factory()->create();
    $fixture = makeAttemptClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 4,
        'question_selection_mode' => QuestionSelectionMode::Random,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $poolIds = collect(range(0, 9))->map(fn ($i) => addAttemptQuestionFixture($exam, $i)['question']->id);
    $krsItem = enrollStudentFixture($university, $fixture['classSection'], $fixture['program']->id);

    actingAsUserWithUniversityPermissions($university, ['exam_attempts.create']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/krs-items/{$krsItem->id}/exams/{$exam->id}/attempt")
        ->assertApiSuccess();

    $assignedIds = collect($response->json('data.questions'))->pluck('id');

    expect($assignedIds)->toHaveCount(4);
    expect($assignedIds->diff($poolIds))->toBeEmpty();
    expect($assignedIds->unique())->toHaveCount(4);
});

test('disabling randomization keeps the configured question and option order', function () {
    $university = University::factory()->create();
    $fixture = makeAttemptClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 3,
        'question_selection_mode' => QuestionSelectionMode::All,
        'randomize_questions' => false,
        'randomize_options' => false,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q0 = addAttemptQuestionFixture($exam, 0);
    $q1 = addAttemptQuestionFixture($exam, 1);
    $q2 = addAttemptQuestionFixture($exam, 2);
    $krsItem = enrollStudentFixture($university, $fixture['classSection'], $fixture['program']->id);

    actingAsUserWithUniversityPermissions($university, ['exam_attempts.create']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/krs-items/{$krsItem->id}/exams/{$exam->id}/attempt")
        ->assertApiSuccess();

    $questions = $response->json('data.questions');

    expect(collect($questions)->pluck('id')->all())->toBe([$q0['question']->id, $q1['question']->id, $q2['question']->id]);
    expect($questions[0]['options'][0]['id'])->toBe($q0['correctOptionId']);
    expect($questions[0]['options'][1]['id'])->toBe($q0['wrongOptionId']);
});

test('option randomization does not break the correct-answer mapping', function () {
    $university = University::factory()->create();
    $fixture = makeAttemptClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'randomize_options' => true,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q = addAttemptQuestionFixture($exam, 0);
    $krsItem = enrollStudentFixture($university, $fixture['classSection'], $fixture['program']->id);

    actingAsUserWithUniversityPermissions($university, ['exam_attempts.create', 'exam_attempts.update']);

    $start = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/krs-items/{$krsItem->id}/exams/{$exam->id}/attempt")
        ->assertApiSuccess();
    $attemptId = $start->json('data.id');

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/exam-attempts/{$attemptId}/answer", [
            'exam_question_id' => $q['question']->id,
            'exam_question_option_id' => $q['correctOptionId'],
        ])
        ->assertApiSuccess();

    $submit = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/exam-attempts/{$attemptId}/submit")
        ->assertApiSuccess();

    expect($submit->json('data.score'))->toBe('100.00');
});

test('answering with the wrong option scores accordingly', function () {
    $university = University::factory()->create();
    $fixture = makeAttemptClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q = addAttemptQuestionFixture($exam, 0);
    $krsItem = enrollStudentFixture($university, $fixture['classSection'], $fixture['program']->id);

    actingAsUserWithUniversityPermissions($university, ['exam_attempts.create', 'exam_attempts.update']);

    $start = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/krs-items/{$krsItem->id}/exams/{$exam->id}/attempt")
        ->assertApiSuccess();
    $attemptId = $start->json('data.id');

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/exam-attempts/{$attemptId}/answer", [
            'exam_question_id' => $q['question']->id,
            'exam_question_option_id' => $q['wrongOptionId'],
        ])
        ->assertApiSuccess();

    $submit = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/exam-attempts/{$attemptId}/submit")
        ->assertApiSuccess();

    expect($submit->json('data.score'))->toBe('0.00');
});

test('starting an attempt for an unpublished exam is rejected', function () {
    $university = University::factory()->create();
    $fixture = makeAttemptClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'is_published' => false,
    ]);
    addAttemptQuestionFixture($exam, 0);
    $krsItem = enrollStudentFixture($university, $fixture['classSection'], $fixture['program']->id);

    actingAsUserWithUniversityPermissions($university, ['exam_attempts.create']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/krs-items/{$krsItem->id}/exams/{$exam->id}/attempt")
        ->assertApiError(409);
});
