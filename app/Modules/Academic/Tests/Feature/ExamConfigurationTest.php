<?php

use App\Support\Tenancy\TenantContext;
use Modules\Academic\Enums\QuestionSelectionMode;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamQuestion;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

function makeExamClassSectionFixture(University $university): ClassSection
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

    return $classSection;
}

/** Creates a question with 2 options (one correct) directly, bypassing the HTTP layer for test setup speed. */
function addExamQuestionFixture(Exam $exam, bool $isSelected = true, int $orderIndex = 0): ExamQuestion
{
    app(TenantContext::class)->setUniversityId($exam->university_id);

    $question = ExamQuestion::factory()->create([
        'exam_id' => $exam->id,
        'order_index' => $orderIndex,
        'is_selected' => $isSelected,
    ]);
    $question->options()->create(['university_id' => $exam->university_id, 'option_text' => 'Benar', 'is_correct' => true, 'order_index' => 0]);
    $question->options()->create(['university_id' => $exam->university_id, 'option_text' => 'Salah', 'is_correct' => false, 'order_index' => 1]);

    app(TenantContext::class)->setUniversityId(null);

    return $question;
}

test('a permitted lecturer can create an exam and add a question with options', function () {
    $university = University::factory()->create();
    $classSection = makeExamClassSectionFixture($university);
    $user = actingAsUserWithUniversityPermissions($university, ['exams.create']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/exams', [
            'class_section_id' => $classSection->id,
            'title' => 'UTS Pemrograman Web',
            'duration_minutes' => 90,
            'questions_per_participant' => 2,
            'question_selection_mode' => 'random',
            'randomize_questions' => true,
            'randomize_options' => true,
        ])
        ->assertApiSuccess(201);

    $examId = $response->json('data.id');

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/exams/{$examId}/questions", [
            'question_text' => 'Apa fungsi utama REST API?',
            'points' => 1,
            'options' => [
                ['option_text' => 'Menghubungkan aplikasi', 'is_correct' => true],
                ['option_text' => 'Menyimpan database', 'is_correct' => false],
            ],
        ])
        ->assertApiSuccess(201);

    $this->assertDatabaseCount('exam_questions', 1);
    $this->assertDatabaseCount('exam_question_options', 2);
});

test('creating an exam without exams.create permission is rejected', function () {
    $university = University::factory()->create();
    $classSection = makeExamClassSectionFixture($university);
    actingAsUserWithUniversityPermissions($university, []);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/exams', [
            'class_section_id' => $classSection->id,
            'title' => 'UTS Pemrograman Web',
            'duration_minutes' => 90,
            'questions_per_participant' => 2,
            'question_selection_mode' => 'all',
        ])
        ->assertApiError(403);
});

test('a question needs exactly one correct option', function () {
    $university = University::factory()->create();
    $classSection = makeExamClassSectionFixture($university);
    actingAsUserWithUniversityPermissions($university, ['exams.create']);

    $exam = Exam::factory()->create(['university_id' => $university->id, 'class_section_id' => $classSection->id]);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/exams/{$exam->id}/questions", [
            'question_text' => 'Soal tanpa jawaban benar',
            'options' => [
                ['option_text' => 'A', 'is_correct' => false],
                ['option_text' => 'B', 'is_correct' => false],
            ],
        ])
        ->assertApiError(422);
});

test('publishing fails when the question pool is empty', function () {
    $university = University::factory()->create();
    $classSection = makeExamClassSectionFixture($university);
    actingAsUserWithUniversityPermissions($university, ['exams.publish']);

    $exam = Exam::factory()->create(['university_id' => $university->id, 'class_section_id' => $classSection->id]);

    $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/exams/{$exam->id}/publish")
        ->assertApiError(409);
});

test('publishing fails when questions per participant exceeds the question pool (scenario: pool=40 per-participant=50)', function () {
    $university = University::factory()->create();
    $classSection = makeExamClassSectionFixture($university);
    actingAsUserWithUniversityPermissions($university, ['exams.publish']);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $classSection->id,
        'questions_per_participant' => 50,
        'question_selection_mode' => QuestionSelectionMode::Random,
    ]);

    // Pool of 40 questions (fewer than the 50 requested per participant).
    for ($i = 0; $i < 40; $i++) {
        addExamQuestionFixture($exam, orderIndex: $i);
    }

    $response = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/exams/{$exam->id}/publish")
        ->assertApiError(409);

    expect($response->json('message'))->toContain('tidak boleh melebihi jumlah soal dalam question pool');
});

test('manual selection mode blocks publish until enough questions are selected (scenario: need 5, selected 3)', function () {
    $university = University::factory()->create();
    $classSection = makeExamClassSectionFixture($university);
    actingAsUserWithUniversityPermissions($university, ['exams.publish']);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $classSection->id,
        'questions_per_participant' => 5,
        'question_selection_mode' => QuestionSelectionMode::Manual,
    ]);

    for ($i = 0; $i < 3; $i++) {
        addExamQuestionFixture($exam, isSelected: true, orderIndex: $i);
    }
    for ($i = 3; $i < 8; $i++) {
        addExamQuestionFixture($exam, isSelected: false, orderIndex: $i);
    }

    $response = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/exams/{$exam->id}/publish")
        ->assertApiError(409);

    expect($response->json('message'))->toBe('Soal belum mencukupi. Anda membutuhkan 5 soal, tetapi baru memilih 3 soal.');
});

test('publishing succeeds once the pool and selection satisfy the configuration', function () {
    $university = University::factory()->create();
    $classSection = makeExamClassSectionFixture($university);
    actingAsUserWithUniversityPermissions($university, ['exams.publish']);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $classSection->id,
        'questions_per_participant' => 3,
        'question_selection_mode' => QuestionSelectionMode::All,
    ]);

    for ($i = 0; $i < 3; $i++) {
        addExamQuestionFixture($exam, orderIndex: $i);
    }

    $response = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/exams/{$exam->id}/publish")
        ->assertApiSuccess();

    expect($response->json('data.is_published'))->toBeTrue();
    expect($response->json('data.question_pool_size'))->toBe(3);
});
