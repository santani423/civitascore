<?php

use App\Support\Tenancy\TenantContext;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\QuestionSelectionMode;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Tenancy\Models\University;

/**
 * Akses ujian publik lewat access_token + NIM, tanpa login sama sekali
 * (spec §3-12) — beda dari StudentExamParticipationTest (self-service
 * Sanctum): di sini tidak ada actingAs() sama sekali, identitas murni
 * datang dari token URL + NIM di body request.
 */
function makePublicExamFixture(University $university): array
{
    app(TenantContext::class)->setUniversityId($university->id);

    $fixture = makeParticipationClassSectionFixture($university);
    $student = Student::factory()->create([
        'university_id' => $university->id,
        'study_program_id' => $fixture['program']->id,
    ]);
    $krsItem = KrsItem::factory()->create([
        'university_id' => $university->id,
        'student_id' => $student->id,
        'class_section_id' => $fixture['classSection']->id,
        'academic_term_id' => $fixture['classSection']->academic_term_id,
        'status' => KrsItemStatus::Enrolled,
    ]);

    app(TenantContext::class)->setUniversityId(null);

    return ['fixture' => $fixture, 'student' => $student, 'krsItem' => $krsItem];
}

test('student can complete the full public nim-based exam flow', function () {
    $university = University::factory()->create();
    $ctx = makePublicExamFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $ctx['fixture']['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'show_result_after_submission' => true,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q = addParticipationQuestionFixture($exam, 0);

    app(TenantContext::class)->setUniversityId($university->id);
    $exam->refresh();
    $exam = app(\Modules\Academic\Services\ExamService::class)->generateAccessToken($exam);
    app(TenantContext::class)->setUniversityId(null);

    $info = $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/public/exams/{$exam->access_token}")
        ->assertApiSuccess();
    expect($info->json('data.title'))->toBe($exam->title);

    $access = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exams/{$exam->access_token}/access", ['nim' => $ctx['student']->nim])
        ->assertApiSuccess();
    $sessionToken = $access->json('data.session_token');
    expect($sessionToken)->not->toBeEmpty();

    $attempt = $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/public/exam-attempts/{$sessionToken}")
        ->assertApiSuccess();
    expect($attempt->json('data.questions.0'))->not->toHaveKey('is_correct');

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/public/exam-attempts/{$sessionToken}/answer", [
            'exam_question_id' => $q['question']->id,
            'exam_question_option_id' => $q['correctOptionId'],
        ])
        ->assertApiSuccess();

    $submit = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/public/exam-attempts/{$sessionToken}/submit")
        ->assertApiSuccess();
    expect($submit->json('data.status'))->toBe('submitted');
    expect($submit->json('data.score'))->toBe('100.00');

    $result = $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/public/exam-attempts/{$sessionToken}/result")
        ->assertApiSuccess();
    expect($result->json('data.summary.score'))->toBe('100.00');
    expect($result->json('data.student.nim'))->toBe($ctx['student']->nim);

    $pdf = $this->withHeader('X-University-ID', $university->id)
        ->get("/api/v1/public/exam-attempts/{$sessionToken}/result/pdf");
    $pdf->assertOk();
    expect($pdf->headers->get('Content-Type'))->toContain('application/pdf');
});

test('unknown nim is rejected with the exact spec message', function () {
    $university = University::factory()->create();
    $ctx = makePublicExamFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $ctx['fixture']['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
    ]);
    addParticipationQuestionFixture($exam, 0);

    app(TenantContext::class)->setUniversityId($university->id);
    $exam = app(\Modules\Academic\Services\ExamService::class)->generateAccessToken($exam);
    app(TenantContext::class)->setUniversityId(null);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exams/{$exam->access_token}/access", ['nim' => '0000000000'])
        ->assertApiError(409);

    expect($response->json('message'))->toBe('NIM mahasiswa tidak ditemukan.');
});

test('exam not yet started returns belum tersedia', function () {
    $university = University::factory()->create();
    $ctx = makePublicExamFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $ctx['fixture']['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
        'starts_at' => now()->addDay(),
    ]);
    addParticipationQuestionFixture($exam, 0);

    app(TenantContext::class)->setUniversityId($university->id);
    $exam = app(\Modules\Academic\Services\ExamService::class)->generateAccessToken($exam);
    app(TenantContext::class)->setUniversityId(null);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exams/{$exam->access_token}/access", ['nim' => $ctx['student']->nim])
        ->assertApiError(409);

    expect($response->json('message'))->toBe('Ujian belum tersedia.');
});

test('exam past its end time returns telah berakhir', function () {
    $university = University::factory()->create();
    $ctx = makePublicExamFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $ctx['fixture']['classSection']->id,
        'is_published' => true,
        'published_at' => now()->subDays(2),
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);
    addParticipationQuestionFixture($exam, 0);

    app(TenantContext::class)->setUniversityId($university->id);
    $exam = app(\Modules\Academic\Services\ExamService::class)->generateAccessToken($exam);
    app(TenantContext::class)->setUniversityId(null);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exams/{$exam->access_token}/access", ['nim' => $ctx['student']->nim])
        ->assertApiError(409);

    expect($response->json('message'))->toBe('Ujian telah berakhir.');
});

test('student who already completed the exam cannot access it again', function () {
    $university = University::factory()->create();
    $ctx = makePublicExamFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $ctx['fixture']['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'max_attempts' => 1,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q = addParticipationQuestionFixture($exam, 0);

    app(TenantContext::class)->setUniversityId($university->id);
    $exam = app(\Modules\Academic\Services\ExamService::class)->generateAccessToken($exam);
    app(TenantContext::class)->setUniversityId(null);

    $access = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exams/{$exam->access_token}/access", ['nim' => $ctx['student']->nim])
        ->assertApiSuccess();
    $sessionToken = $access->json('data.session_token');

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/public/exam-attempts/{$sessionToken}/answer", [
            'exam_question_id' => $q['question']->id,
            'exam_question_option_id' => $q['correctOptionId'],
        ])
        ->assertApiSuccess();
    $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/public/exam-attempts/{$sessionToken}/submit")
        ->assertApiSuccess();

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exams/{$exam->access_token}/access", ['nim' => $ctx['student']->nim])
        ->assertApiError(409);

    expect($response->json('message'))->toBe('Anda sudah menyelesaikan ujian ini.');
});

test('answering with a question outside the assigned snapshot is rejected', function () {
    $university = University::factory()->create();
    $ctx = makePublicExamFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $ctx['fixture']['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::Manual,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $selected = addParticipationQuestionFixture($exam, 0);
    $notSelected = addParticipationQuestionFixture($exam, 1);
    $notSelected['question']->update(['is_selected' => false]);

    app(TenantContext::class)->setUniversityId($university->id);
    $exam = app(\Modules\Academic\Services\ExamService::class)->generateAccessToken($exam);
    app(TenantContext::class)->setUniversityId(null);

    $access = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exams/{$exam->access_token}/access", ['nim' => $ctx['student']->nim])
        ->assertApiSuccess();
    $sessionToken = $access->json('data.session_token');

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/public/exam-attempts/{$sessionToken}/answer", [
            'exam_question_id' => $notSelected['question']->id,
            'exam_question_option_id' => $notSelected['correctOptionId'],
        ])
        ->assertApiError(409);
});

test('regenerating the access link invalidates the old link but keeps issued session tokens working', function () {
    $university = University::factory()->create();
    $ctx = makePublicExamFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $ctx['fixture']['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
    ]);
    addParticipationQuestionFixture($exam, 0);

    $examService = app(\Modules\Academic\Services\ExamService::class);
    app(TenantContext::class)->setUniversityId($university->id);
    $exam = $examService->generateAccessToken($exam);
    app(TenantContext::class)->setUniversityId(null);

    $oldToken = $exam->access_token;

    $access = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exams/{$oldToken}/access", ['nim' => $ctx['student']->nim])
        ->assertApiSuccess();
    $sessionToken = $access->json('data.session_token');

    app(TenantContext::class)->setUniversityId($university->id);
    $exam = $examService->generateAccessToken($exam->fresh());
    app(TenantContext::class)->setUniversityId(null);

    expect($exam->access_token)->not->toBe($oldToken);

    $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/public/exams/{$oldToken}")
        ->assertStatus(404);

    $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/public/exams/{$exam->access_token}")
        ->assertApiSuccess();

    // Sesi yang sudah dikeluarkan sebelum regenerate tetap jalan — hanya
    // link masuk yang lama yang tidak valid, bukan attempt yang sedang berjalan.
    $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/public/exam-attempts/{$sessionToken}")
        ->assertApiSuccess();
});
