<?php

use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\QuestionBankItem;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

function makeQuestionBankClassSectionFixture(University $university): ClassSection
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

/**
 * @return array{item: QuestionBankItem, correctOptionId: string, wrongOptionId: string}
 */
function addQuestionBankItemFixture(University $university): array
{
    app(TenantContext::class)->setUniversityId($university->id);

    $item = QuestionBankItem::factory()->create(['university_id' => $university->id]);
    $correct = $item->options()->create(['university_id' => $university->id, 'option_text' => 'Benar', 'is_correct' => true, 'order_index' => 0]);
    $wrong = $item->options()->create(['university_id' => $university->id, 'option_text' => 'Salah', 'is_correct' => false, 'order_index' => 1]);

    app(TenantContext::class)->setUniversityId(null);

    return ['item' => $item, 'correctOptionId' => $correct->id, 'wrongOptionId' => $wrong->id];
}

test('a permitted lecturer can create a question bank item with options', function () {
    $university = University::factory()->create();
    actingAsUserWithUniversityPermissions($university, ['question_bank.create']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/question-bank', [
            'question_text' => 'Apa fungsi utama REST API?',
            'points' => 2,
            'options' => [
                ['option_text' => 'Menghubungkan aplikasi', 'is_correct' => true],
                ['option_text' => 'Menyimpan database', 'is_correct' => false],
            ],
        ])
        ->assertApiSuccess(201);

    expect($response->json('data.question_text'))->toBe('Apa fungsi utama REST API?');
    $this->assertDatabaseCount('question_bank_items', 1);
    $this->assertDatabaseCount('question_bank_item_options', 2);
});

test('creating a question bank item without permission is rejected', function () {
    $university = University::factory()->create();
    actingAsUserWithUniversityPermissions($university, []);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson('/api/v1/question-bank', [
            'question_text' => 'Soal apa saja',
            'options' => [
                ['option_text' => 'A', 'is_correct' => true],
                ['option_text' => 'B', 'is_correct' => false],
            ],
        ])
        ->assertApiError(403);
});

test('a permitted lecturer can list question bank items', function () {
    $university = University::factory()->create();
    addQuestionBankItemFixture($university);
    addQuestionBankItemFixture($university);
    actingAsUserWithUniversityPermissions($university, ['question_bank.read']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/question-bank')
        ->assertApiSuccess();

    expect($response->json('meta.total'))->toBe(2);
});

test('applying question bank items to an exam copies them as exam questions with correct options preserved', function () {
    $university = University::factory()->create();
    $classSection = makeQuestionBankClassSectionFixture($university);
    $exam = Exam::factory()->create(['university_id' => $university->id, 'class_section_id' => $classSection->id]);

    $bankQuestion = addQuestionBankItemFixture($university);
    actingAsUserWithUniversityPermissions($university, ['exams.update']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/exams/{$exam->id}/questions/apply-bank", [
            'question_bank_item_ids' => [$bankQuestion['item']->id],
        ])
        ->assertApiSuccess();

    expect($response->json('data'))->toHaveCount(1);
    $this->assertDatabaseCount('exam_questions', 1);
    $this->assertDatabaseCount('exam_question_options', 2);

    $examQuestion = $exam->questions()->first();
    expect($examQuestion->question_bank_item_id)->toBe($bankQuestion['item']->id);
    expect($examQuestion->options()->where('is_correct', true)->count())->toBe(1);

    // The bank item's own options remain untouched — this was a copy, not a move.
    $this->assertDatabaseCount('question_bank_item_options', 2);
});

test('applying the same question bank item to an exam twice does not duplicate it', function () {
    $university = University::factory()->create();
    $classSection = makeQuestionBankClassSectionFixture($university);
    $exam = Exam::factory()->create(['university_id' => $university->id, 'class_section_id' => $classSection->id]);

    $bankQuestion = addQuestionBankItemFixture($university);
    actingAsUserWithUniversityPermissions($university, ['exams.update']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/exams/{$exam->id}/questions/apply-bank", [
            'question_bank_item_ids' => [$bankQuestion['item']->id],
        ])
        ->assertApiSuccess();

    $second = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/exams/{$exam->id}/questions/apply-bank", [
            'question_bank_item_ids' => [$bankQuestion['item']->id],
        ])
        ->assertApiSuccess();

    expect($second->json('data'))->toHaveCount(0);
    $this->assertDatabaseCount('exam_questions', 1);
});

test('applying bank questions to a published exam is rejected', function () {
    $university = University::factory()->create();
    $classSection = makeQuestionBankClassSectionFixture($university);
    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $classSection->id,
        'is_published' => true,
    ]);

    $bankQuestion = addQuestionBankItemFixture($university);
    actingAsUserWithUniversityPermissions($university, ['exams.update']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/exams/{$exam->id}/questions/apply-bank", [
            'question_bank_item_ids' => [$bankQuestion['item']->id],
        ])
        ->assertApiError(409);
});

test('editing a question bank item after applying it does not change the exam question already created', function () {
    $university = University::factory()->create();
    $classSection = makeQuestionBankClassSectionFixture($university);
    $exam = Exam::factory()->create(['university_id' => $university->id, 'class_section_id' => $classSection->id]);

    $bankQuestion = addQuestionBankItemFixture($university);
    actingAsUserWithUniversityPermissions($university, ['exams.update', 'question_bank.update']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/exams/{$exam->id}/questions/apply-bank", [
            'question_bank_item_ids' => [$bankQuestion['item']->id],
        ])
        ->assertApiSuccess();

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/question-bank/{$bankQuestion['item']->id}", [
            'question_text' => 'Teks soal sudah diubah',
        ])
        ->assertApiSuccess();

    $examQuestion = $exam->questions()->first();
    expect($examQuestion->question_text)->not->toBe('Teks soal sudah diubah');
});
