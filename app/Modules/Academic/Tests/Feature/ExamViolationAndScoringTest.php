<?php

use App\Support\Tenancy\TenantContext;
use Modules\Academic\Enums\QuestionSelectionMode;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Services\ExamService;
use Modules\Tenancy\Models\University;

/**
 * Pelanggaran (-1 poin/pelanggaran), rentang nilai, bobot, rekap nilai, dan
 * download naskah ujian — dibangun di atas percobaan ujian yang sudah ada
 * (exam_attempts), lihat ExamService::recordViolation()/resolveGrade()/
 * upsertGradeRanges(). Memakai fixture yang sama seperti
 * StudentExamParticipationTest (makeParticipationClassSectionFixture/
 * addParticipationQuestionFixture) dan PublicExamAccessTest
 * (makePublicExamFixture) yang sudah didefinisikan di file test lain.
 */
test('each violation deducts one point and is recorded in sequence for the lecturer timeline', function () {
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

    foreach (['tab_switch', 'window_blur', 'fullscreen_exit'] as $type) {
        $this->withHeader('X-University-ID', $university->id)
            ->postJson("/api/v1/student/exam-attempts/{$attemptId}/violations", ['violation_type' => $type])
            ->assertApiSuccess();
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

    expect($submit->json('data.raw_score'))->toBe('100.00');
    expect($submit->json('data.penalty_score'))->toBe('3.00');
    expect($submit->json('data.score'))->toBe('97.00');
    expect($submit->json('data.violation_count'))->toBe(3);

    actingAsUserWithUniversityPermissions($university, ['exam_attempts.read']);

    $timeline = $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/exam-attempts/{$attemptId}/violations")
        ->assertApiSuccess();

    expect($timeline->json('data.raw_score'))->toBe('100.00');
    expect($timeline->json('data.score'))->toBe('97.00');
    expect($timeline->json('data.violation_count'))->toBe(3);

    $violations = $timeline->json('data.violations');
    expect($violations)->toHaveCount(3);
    expect($violations[0]['sequence_number'])->toBe(1);
    expect($violations[0]['violation_type'])->toBe('tab_switch');
    expect($violations[1]['sequence_number'])->toBe(2);
    expect($violations[2]['sequence_number'])->toBe(3);
    expect($violations[2]['violation_type'])->toBe('fullscreen_exit');
});

test('final score never drops below zero even with penalties exceeding the raw score', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'is_published' => true,
        'published_at' => now(),
    ]);
    addParticipationQuestionFixture($exam, 0);

    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);

    $start = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiSuccess();
    $attemptId = $start->json('data.id');

    foreach (range(1, 5) as $i) {
        $this->withHeader('X-University-ID', $university->id)
            ->postJson("/api/v1/student/exam-attempts/{$attemptId}/violations", ['violation_type' => 'copy_attempt'])
            ->assertApiSuccess();
    }

    $submit = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/student/exam-attempts/{$attemptId}/submit")
        ->assertApiSuccess();

    expect($submit->json('data.raw_score'))->toBe('0.00');
    expect($submit->json('data.penalty_score'))->toBe('5.00');
    expect($submit->json('data.score'))->toBe('0.00');
});

test('violations cannot be recorded after the exam has been submitted', function () {
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

    $start = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiSuccess();
    $attemptId = $start->json('data.id');

    $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/student/exam-attempts/{$attemptId}/submit")
        ->assertApiSuccess();

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exam-attempts/{$attemptId}/violations", ['violation_type' => 'tab_switch'])
        ->assertApiError(409);
});

test('student cannot record a violation on another students attempt', function () {
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
    $start = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exams/{$exam->id}/start")
        ->assertApiSuccess();
    $attemptId = $start->json('data.id');

    actingAsEnrolledStudent($university, $fixture['classSection'], $fixture['program']->id);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/student/exam-attempts/{$attemptId}/violations", ['violation_type' => 'tab_switch'])
        ->assertStatus(403);
});

test('grade resolves from configured exam grade ranges and falls back to default thresholds otherwise', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q = addParticipationQuestionFixture($exam, 0);

    app(TenantContext::class)->setUniversityId($university->id);
    app(ExamService::class)->upsertGradeRanges($exam, [
        ['grade' => 'A', 'min_score' => 90, 'max_score' => 100],
        ['grade' => 'B', 'min_score' => 0, 'max_score' => 89.99],
    ]);
    app(TenantContext::class)->setUniversityId(null);

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

    // raw_score 100, tanpa penalti -> masuk rentang A (90-100).
    expect($submit->json('data.grade'))->toBe('A');
});

test('grade range update rejects overlapping ranges', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    actingAsUserWithUniversityPermissions($university, ['exams.read', 'exams.update']);

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/exams/{$exam->id}/grade-ranges", [
            'ranges' => [
                ['grade' => 'A', 'min_score' => 80, 'max_score' => 100],
                ['grade' => 'B', 'min_score' => 70, 'max_score' => 85],
            ],
        ])
        ->assertStatus(422);
});

test('weighted score is computed from the exam weight percentage when configured', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'weight_percentage' => 30,
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

    expect($submit->json('data.score'))->toBe('100.00');
    expect($submit->json('data.weighted_score'))->toBe('30.00');
});

test('recap endpoint returns exam-level summary and per-student scoring breakdown', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
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
    $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/student/exam-attempts/{$attemptId}/submit")
        ->assertApiSuccess();

    actingAsUserWithUniversityPermissions($university, ['exams.read']);

    $recap = $this->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/exams/{$exam->id}/recap")
        ->assertApiSuccess();

    expect($recap->json('data.summary.total_participants'))->toBe(1);
    expect($recap->json('data.summary.completed'))->toBe(1);
    expect((float) $recap->json('data.summary.average_score'))->toBe(100.0);

    $row = $recap->json('data.participants.0');
    expect($row['score'])->toBe('100.00');
    expect($row['raw_score'])->toBe('100.00');
    expect($row['violation_count'])->toBe(0);
});

test('exam pdf download only includes the answer key for users with manage permission', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'is_published' => true,
        'published_at' => now(),
    ]);
    addParticipationQuestionFixture($exam, 0);

    actingAsUserWithUniversityPermissions($university, ['exams.read']);
    $this->withHeader('X-University-ID', $university->id)
        ->get("/api/v1/exams/{$exam->id}/download?with_answers=1")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    actingAsUserWithUniversityPermissions($university, ['exams.read', 'exams.update']);
    $this->withHeader('X-University-ID', $university->id)
        ->get("/api/v1/exams/{$exam->id}/download?with_answers=1")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('public exam flow also records violations and applies penalties', function () {
    $university = University::factory()->create();
    $fixture = makeParticipationClassSectionFixture($university);
    $student = \Modules\Academic\Models\Student::factory()->create([
        'university_id' => $university->id,
        'study_program_id' => $fixture['program']->id,
    ]);
    app(TenantContext::class)->setUniversityId($university->id);
    KrsItem::factory()->create([
        'university_id' => $university->id,
        'student_id' => $student->id,
        'class_section_id' => $fixture['classSection']->id,
        'academic_term_id' => $fixture['classSection']->academic_term_id,
        'status' => \Modules\Academic\Enums\KrsItemStatus::Enrolled,
    ]);
    app(TenantContext::class)->setUniversityId(null);

    $exam = Exam::factory()->create([
        'university_id' => $university->id,
        'class_section_id' => $fixture['classSection']->id,
        'questions_per_participant' => 1,
        'question_selection_mode' => QuestionSelectionMode::All,
        'is_published' => true,
        'published_at' => now(),
    ]);
    $q = addParticipationQuestionFixture($exam, 0);

    app(TenantContext::class)->setUniversityId($university->id);
    $exam = app(ExamService::class)->generateAccessToken($exam);
    app(TenantContext::class)->setUniversityId(null);

    $access = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exams/{$exam->access_token}/access", ['nim' => $student->nim])
        ->assertApiSuccess();
    $sessionToken = $access->json('data.session_token');

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/public/exam-attempts/{$sessionToken}/violations", ['violation_type' => 'paste_attempt'])
        ->assertApiSuccess();

    $this->withHeader('X-University-ID', $university->id)
        ->putJson("/api/v1/public/exam-attempts/{$sessionToken}/answer", [
            'exam_question_id' => $q['question']->id,
            'exam_question_option_id' => $q['correctOptionId'],
        ])
        ->assertApiSuccess();

    $submit = $this->withHeader('X-University-ID', $university->id)
        ->patchJson("/api/v1/public/exam-attempts/{$sessionToken}/submit")
        ->assertApiSuccess();

    expect($submit->json('data.raw_score'))->toBe('100.00');
    expect($submit->json('data.penalty_score'))->toBe('1.00');
    expect($submit->json('data.score'))->toBe('99.00');
});
