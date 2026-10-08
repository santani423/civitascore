<?php

use App\Models\User;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Support\AcademicFeature;
use Modules\SystemSetting\Models\FeatureFlag;
use Modules\SystemSetting\Services\FeatureFlagService;
use Modules\Tenancy\Models\University;

/*
| Tahap 0.8 / AC-15 — bila flag `academic.lecturer_ownership` menyala untuk
| universitas, dosen hanya bisa melihat dan menulis nilai, absensi, dan ujian
| kelas yang diampunya. Bagian Akademik (classes.update) dan pemegang hak
| baca saja (pimpinan) tidak dibatasi. Flag mati = perilaku lama.
*/

/** Permission role `lecturer` yang relevan (OrganizationalRoleSeeder). */
const OWNERSHIP_LECTURER_PERMISSIONS = [
    'classes.read', 'krs.read', 'students.read',
    'grades.read', 'grades.create', 'grades.update',
    'attendance.read', 'attendance.create', 'attendance.update',
    'exams.read', 'exams.create', 'exams.update', 'exams.delete', 'exams.publish',
    'exam_attempts.read', 'exam_attempts.create', 'exam_attempts.update',
];

/**
 * Dua kelas dengan peserta yang sama: A diampu dosen yang login, B diampu
 * dosen lain.
 *
 * @return array<string, mixed>
 */
function ownershipWorld(bool $enforced = true): array
{
    $world = portalWorld();
    $mine = portalLecturer($world);
    $theirs = portalLecturer($world);
    portalGrant($world, $mine['user'], OWNERSHIP_LECTURER_PERMISSIONS);

    $classA = portalClass($world, portalCourse($world, ['name' => 'Basis Data']), [], ['lecturer_id' => $mine['lecturer']->id, 'class_code' => 'A']);
    $classB = portalClass($world, portalCourse($world, ['name' => 'Statistika']), [], ['lecturer_id' => $theirs['lecturer']->id, 'class_code' => 'B']);
    $itemA = portalEnroll($world, $world['student'], $classA);
    $itemB = portalEnroll($world, $world['student'], $classB);

    $flag = FeatureFlag::factory()->create(['key' => AcademicFeature::LECTURER_OWNERSHIP, 'is_enabled' => false]);

    if ($enforced) {
        app(FeatureFlagService::class)->setOverride($flag, $world['university']->id, true);
    }

    return [...$world, 'mine' => $mine, 'classA' => $classA, 'classB' => $classB, 'itemA' => $itemA, 'itemB' => $itemB, 'flag' => $flag];
}

/** Ujian di kelas B, dibuat Bagian Akademik. */
function ownershipForeignExam(array $world, ClassSection $classSection): string
{
    actingAsUserWithUniversityPermissions($world['university'], ['classes.update', 'exams.create']);

    return test()->withHeaders(portalHeaders($world))->postJson('/api/v1/exams', [
        'class_section_id' => $classSection->id,
        'title' => 'UTS Statistika',
        'duration_minutes' => 60,
        'questions_per_participant' => 1,
        'question_selection_mode' => 'all',
    ])->assertApiSuccess(201)->json('data.id');
}

test('a lecturer grades and takes attendance only in their own class', function () {
    $world = ownershipWorld();
    $this->actingAs($world['mine']['user']);
    $headers = portalHeaders($world);

    $this->withHeaders($headers)->putJson("/api/v1/krs-items/{$world['itemA']->id}/grade", ['score' => 85])->assertApiSuccess();
    $this->withHeaders($headers)->putJson("/api/v1/krs-items/{$world['itemB']->id}/grade", ['score' => 85])
        ->assertApiError(403)
        ->assertJsonPath('message', 'Anda bukan dosen pengampu kelas ini.');

    $attendance = fn (ClassSection $classSection, string $itemId) => $this->withHeaders($headers)
        ->postJson("/api/v1/class-sections/{$classSection->id}/attendances", [
            'meeting_number' => 1,
            'meeting_date' => now()->toDateString(),
            'entries' => [['krs_item_id' => $itemId, 'status' => 'present']],
        ]);

    $attendance($world['classA'], $world['itemA']->id)->assertApiSuccess(201);
    $attendance($world['classB'], $world['itemB']->id)->assertApiError(403);
    // KRS kelas lain diselipkan ke kelas sendiri tetap ditolak.
    $attendance($world['classA'], $world['itemB']->id)->assertApiError(409);
});

test('a lecturer only sees their own classes, participants, grades and attendance', function () {
    $world = ownershipWorld();

    // Data kelas B direkam Bagian Akademik — dosen lain tidak boleh melihatnya.
    actingAsUserWithUniversityPermissions($world['university'], ['classes.update', 'grades.create', 'attendance.create']);
    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/krs-items/{$world['itemB']->id}/grade", ['score' => 70])->assertApiSuccess();
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/class-sections/{$world['classB']->id}/attendances", [
        'meeting_number' => 1, 'meeting_date' => now()->toDateString(),
        'entries' => [['krs_item_id' => $world['itemB']->id, 'status' => 'present']],
    ])->assertApiSuccess(201);

    $this->actingAs($world['mine']['user']);
    $headers = portalHeaders($world);
    $this->withHeaders($headers)->putJson("/api/v1/krs-items/{$world['itemA']->id}/grade", ['score' => 90])->assertApiSuccess();

    $this->withHeaders($headers)->getJson('/api/v1/class-sections')
        ->assertApiSuccess()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $world['classA']->id);
    $this->withHeaders($headers)->getJson("/api/v1/class-sections/{$world['classA']->id}")->assertApiSuccess();
    $this->withHeaders($headers)->getJson("/api/v1/class-sections/{$world['classB']->id}")->assertApiError(403);

    // Filter id kelas lain tidak membuka datanya.
    $this->withHeaders($headers)->getJson("/api/v1/grades?filter[class_section_id]={$world['classB']->id}")->assertJsonPath('meta.total', 0);
    $this->withHeaders($headers)->getJson('/api/v1/grades')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.krs_item_id', $world['itemA']->id);
    $this->withHeaders($headers)->getJson('/api/v1/attendances')->assertJsonPath('meta.total', 0);
    $this->withHeaders($headers)->getJson('/api/v1/krs-items')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $world['itemA']->id);

    // Pimpinan (hak baca saja) tetap melihat semua kelas.
    actingAsUserWithUniversityPermissions($world['university'], ['classes.read', 'grades.read']);
    $this->withHeaders($headers)->getJson('/api/v1/class-sections')->assertJsonPath('meta.total', 2);
    $this->withHeaders($headers)->getJson('/api/v1/grades')->assertJsonPath('meta.total', 2);
});

test('the academic office still manages every class while ownership is enforced', function () {
    $world = ownershipWorld();
    actingAsUserWithUniversityPermissions($world['university'], ['classes.read', 'classes.update', 'grades.create']);

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/krs-items/{$world['itemB']->id}/grade", ['score' => 75])->assertApiSuccess();
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/class-sections')->assertJsonPath('meta.total', 2);
});

test('a lecturer can only create and manage exams of their own class', function () {
    $world = ownershipWorld();
    $foreignExamId = ownershipForeignExam($world, $world['classB']);

    $this->actingAs($world['mine']['user']);
    $headers = portalHeaders($world);
    $payload = fn (ClassSection $classSection) => [
        'class_section_id' => $classSection->id, 'title' => 'Kuis', 'duration_minutes' => 30,
        'questions_per_participant' => 1, 'question_selection_mode' => 'all',
    ];

    $ownExamId = $this->withHeaders($headers)->postJson('/api/v1/exams', $payload($world['classA']))->assertApiSuccess(201)->json('data.id');
    $this->withHeaders($headers)->postJson('/api/v1/exams', $payload($world['classB']))->assertApiError(403);

    $this->withHeaders($headers)->getJson('/api/v1/exams')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $ownExamId);

    $this->withHeaders($headers)->getJson("/api/v1/exams/{$foreignExamId}")->assertApiError(403);
    $this->withHeaders($headers)->putJson("/api/v1/exams/{$foreignExamId}", ['title' => 'Diubah'])->assertApiError(403);
    $this->withHeaders($headers)->patchJson("/api/v1/exams/{$foreignExamId}/publish")->assertApiError(403);
    $this->withHeaders($headers)->deleteJson("/api/v1/exams/{$foreignExamId}")->assertApiError(403);
    $this->withHeaders($headers)->postJson("/api/v1/exams/{$foreignExamId}/questions", [
        'question_text' => 'Soal?', 'points' => 1,
        'options' => [['option_text' => 'Ya', 'is_correct' => true], ['option_text' => 'Tidak', 'is_correct' => false]],
    ])->assertApiError(403);
    $this->withHeaders($headers)->getJson("/api/v1/exams/{$foreignExamId}/participants")->assertApiError(403);
    $this->withHeaders($headers)->getJson("/api/v1/exams/{$ownExamId}/participants")->assertApiSuccess();
});

test('ownership is not enforced while the flag is off, or when it is on only for another university', function () {
    $world = ownershipWorld(enforced: false);
    app(FeatureFlagService::class)->setOverride($world['flag'], University::factory()->create()->id, true);

    $this->actingAs($world['mine']['user']);
    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/krs-items/{$world['itemB']->id}/grade", ['score' => 60])->assertApiSuccess();
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/class-sections')->assertJsonPath('meta.total', 2);
});

test('a lecturer account that is not linked to any lecturer record manages no class', function () {
    $world = ownershipWorld();
    $stranger = User::factory()->create();
    portalGrant($world, $stranger, OWNERSHIP_LECTURER_PERMISSIONS);

    $this->actingAs($stranger);
    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/krs-items/{$world['itemA']->id}/grade", ['score' => 85])->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/class-sections')->assertJsonPath('meta.total', 0);
});
