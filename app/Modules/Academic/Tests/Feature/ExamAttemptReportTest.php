<?php

use Modules\Academic\Enums\QuestionSelectionMode;
use Modules\Academic\Models\Exam;
use Modules\Tenancy\Models\University;

/**
 * "Detail Report" satu percobaan untuk dosen (ExamAttemptController::report/
 * reportPdf, ExamService::buildAttemptReport). Memakai fixture dari
 * StudentExamParticipationTest (makeParticipationClassSectionFixture/
 * addParticipationQuestionFixture/actingAsEnrolledStudent).
 */
test('lecturer report details timing, answers, violations and timeline of an attempt', function () {
    $university = University::factory()->create(['timezone' => 'Asia/Jayapura']);
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'duration_minutes' => 60,
        'questions_per_participant' => 3,
        'question_selection_mode' => QuestionSelectionMode::All,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q1 = addParticipationQuestionFixture($exam, 0);
    $q2 = addParticipationQuestionFixture($exam, 1);
    addParticipationQuestionFixture($exam, 2);

    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);

    $attemptId = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiSuccess()
        ->json('data.id');

    $this->travel(2)->minutes();
    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/student/exam-attempts/{$attemptId}/answer", [
            'exam_question_id' => $q1['question']->id,
            'exam_question_option_id' => $q1['correctOptionId'],
        ])
        ->assertApiSuccess();

    $this->travel(1)->minutes();
    foreach (['tab_switch', 'tab_switch', 'copy_attempt'] as $type) {
        $this->withHeader('X-University-ID', $university->id)
            ->postJson("/api/v1/student/exam-attempts/{$attemptId}/violations", ['violation_type' => $type])
            ->assertApiSuccess();
    }

    $this->travel(2)->minutes();
    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/student/exam-attempts/{$attemptId}/answer", [
            'exam_question_id' => $q2['question']->id,
            'exam_question_option_id' => $q2['wrongOptionId'],
        ])
        ->assertApiSuccess();

    $this->travel(5)->minutes();
    $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/student/exam-attempts/{$attemptId}/submit")
        ->assertApiSuccess();

    // Hasil versi mahasiswa ikut terkoreksi: durasi tidak lagi negatif.
    $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/student/exam-attempts/{$attemptId}/result")
        ->assertApiSuccess()
        ->assertJsonPath('data.attempt.duration_seconds', 600);

    actingAsUserWithUniversityPermissions($university, ['exam_attempts.read']);

    $report = $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/exam-attempts/{$attemptId}/report")
        ->assertApiSuccess();

    expect($report->json('data.timezone'))->toBe('Asia/Jayapura');
    expect($report->json('data.attempt.status'))->toBe('submitted');
    expect($report->json('data.attempt.duration_seconds'))->toBe(600);
    expect($report->json('data.attempt.allowed_seconds'))->toBe(3600);
    expect($report->json('data.attempt.submission_type'))->toBe('manual');

    expect($report->json('data.summary'))->toMatchArray([
        'total_questions' => 3,
        'answered' => 2,
        'correct_answers' => 1,
        'wrong_answers' => 1,
        'unanswered' => 1,
        'violation_count' => 3,
        'penalty_score' => '3.00',
    ]);

    $questions = $report->json('data.questions');
    expect(array_column($questions, 'status'))->toBe(['correct', 'wrong', 'unanswered']);
    expect($questions[0]['selected_option_label'])->toBe('A');
    expect($questions[0]['correct_option_label'])->toBe('A');
    expect($questions[0]['answered_after_seconds'])->toBe(120);
    expect($questions[1]['selected_option_label'])->toBe('B');
    expect($questions[1]['answered_after_seconds'])->toBe(300);
    expect($questions[2]['answered_at'])->toBeNull();

    $violations = $report->json('data.violations');
    expect($violations)->toHaveCount(3);
    expect($violations[0]['occurred_after_seconds'])->toBe(180);

    $breakdown = $report->json('data.violation_breakdown');
    expect($breakdown[0])->toMatchArray(['violation_type' => 'tab_switch', 'count' => 2, 'penalty_points' => '2.00']);
    expect($breakdown[1])->toMatchArray(['violation_type' => 'copy_attempt', 'count' => 1]);

    $timelineTypes = array_column($report->json('data.timeline'), 'type');
    expect($timelineTypes)->toBe(['started', 'answered', 'violation', 'violation', 'violation', 'answered', 'submitted']);

    expect($report->json('data.attempts'))->toHaveCount(1);

    $this->withHeader('X-University-ID', $university->id)
        ->get("/api/v1/exam-attempts/{$attemptId}/report/pdf")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('lecturer report finalizes an attempt whose time has run out as an automatic submission', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'duration_minutes' => 30,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'is_published' => true,
        'published_at' => now(),
    ]);
    addParticipationQuestionFixture($exam, 0);

    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);

    $attemptId = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiSuccess()
        ->json('data.id');

    $this->travel(45)->minutes();

    actingAsUserWithUniversityPermissions($university, ['exam_attempts.read']);

    $report = $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/exam-attempts/{$attemptId}/report")
        ->assertApiSuccess();

    expect($report->json('data.attempt.status'))->toBe('submitted');
    expect($report->json('data.attempt.submission_type'))->toBe('auto_timeout');
    expect($report->json('data.attempt.duration_seconds'))->toBe(1800);
    expect($report->json('data.summary.unanswered'))->toBe(1);
    expect(array_column($report->json('data.timeline'), 'type'))->toBe(['started', 'submitted']);
});

test('lecturer report requires the exam attempts read permission', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
    ]);
    addParticipationQuestionFixture($exam, 0);

    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);

    $attemptId = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiSuccess()
        ->json('data.id');

    // Mahasiswa pemilik attempt pun tidak boleh membuka laporan versi dosen
    // (yang selalu menyertakan kunci jawaban).
    $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/exam-attempts/{$attemptId}/report")
        ->assertForbidden();

    actingAsUserWithUniversityPermissions($university, ['exams.read']);

    $this->withHeader('X-University-ID', $university->id)
        ->get("/api/v1/exam-attempts/{$attemptId}/report/pdf")
        ->assertForbidden();
});
