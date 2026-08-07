<?php

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Str;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

function makeClassRosterFixture(University $university, int $studentCount = 2): array
{
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $term = AcademicTerm::factory()->create(['university_id' => $university->id, 'is_current' => true]);
    $course = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $classSection = ClassSection::factory()->create([
        'university_id' => $university->id, 'study_program_id' => $program->id,
        'academic_term_id' => $term->id, 'course_id' => $course->id,
    ]);

    $krsItems = collect(range(1, $studentCount))->map(function () use ($university, $program, $classSection, $term) {
        $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);

        return KrsItem::factory()->create([
            'university_id' => $university->id, 'student_id' => $student->id,
            'class_section_id' => $classSection->id, 'academic_term_id' => $term->id,
            'status' => KrsItemStatus::Enrolled,
        ]);
    });

    app(TenantContext::class)->setUniversityId(null);

    return compact('classSection', 'krsItems');
}

test('a permitted user can record attendance for a whole class meeting at once', function () {
    $university = University::factory()->create();
    $fixture = makeClassRosterFixture($university, 2);

    actingAsUserWithUniversityPermissions($university, ['attendance.create']);

    $response = $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/class-sections/{$fixture['classSection']->id}/attendances", [
            'meeting_number' => 1,
            'meeting_date' => now()->toDateString(),
            'entries' => [
                ['krs_item_id' => $fixture['krsItems'][0]->id, 'status' => 'present'],
                ['krs_item_id' => $fixture['krsItems'][1]->id, 'status' => 'absent', 'notes' => 'Tanpa keterangan'],
            ],
        ])
        ->assertApiSuccess(201);

    expect($response->json('data'))->toHaveCount(2);
    $this->assertDatabaseHas('attendances', ['krs_item_id' => $fixture['krsItems'][0]->id, 'status' => 'present']);
    $this->assertDatabaseHas('attendances', ['krs_item_id' => $fixture['krsItems'][1]->id, 'status' => 'absent']);
});

test('resubmitting the same meeting updates the existing attendance rows', function () {
    $university = University::factory()->create();
    $fixture = makeClassRosterFixture($university, 1);

    actingAsUserWithUniversityPermissions($university, ['attendance.create', 'attendance.update']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/class-sections/{$fixture['classSection']->id}/attendances", [
            'meeting_number' => 1,
            'meeting_date' => now()->toDateString(),
            'entries' => [['krs_item_id' => $fixture['krsItems'][0]->id, 'status' => 'absent']],
        ])
        ->assertApiSuccess(201);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/class-sections/{$fixture['classSection']->id}/attendances", [
            'meeting_number' => 1,
            'meeting_date' => now()->toDateString(),
            'entries' => [['krs_item_id' => $fixture['krsItems'][0]->id, 'status' => 'present']],
        ])
        ->assertApiSuccess(201);

    $this->assertDatabaseCount('attendances', 1);
    $this->assertDatabaseHas('attendances', ['krs_item_id' => $fixture['krsItems'][0]->id, 'status' => 'present']);
});

test('recording attendance for a student not enrolled in the class is rejected', function () {
    $university = University::factory()->create();
    $fixture = makeClassRosterFixture($university, 1);

    // ULID acak yang bukan milik siapa pun di roster kelas ini — cukup
    // untuk membuktikan service menolak id di luar peserta aktif kelas,
    // tanpa perlu fixture universitas/term kedua yang penuh.
    $outsiderKrsItemId = (string) Str::ulid();

    actingAsUserWithUniversityPermissions($university, ['attendance.create']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/class-sections/{$fixture['classSection']->id}/attendances", [
            'meeting_number' => 1,
            'meeting_date' => now()->toDateString(),
            'entries' => [['krs_item_id' => $outsiderKrsItemId, 'status' => 'present']],
        ])
        ->assertApiError(409);
});

test('recording attendance without permission is rejected', function () {
    $university = University::factory()->create();
    $fixture = makeClassRosterFixture($university, 1);

    actingAsUserWithUniversityPermissions($university, []);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/class-sections/{$fixture['classSection']->id}/attendances", [
            'meeting_number' => 1,
            'meeting_date' => now()->toDateString(),
            'entries' => [['krs_item_id' => $fixture['krsItems'][0]->id, 'status' => 'present']],
        ])
        ->assertApiError(403);
});

test('an invalid attendance status is rejected by validation', function () {
    $university = University::factory()->create();
    $fixture = makeClassRosterFixture($university, 1);

    actingAsUserWithUniversityPermissions($university, ['attendance.create']);

    $this->withHeader('X-University-ID', $university->id)
        ->postJson("/api/v1/class-sections/{$fixture['classSection']->id}/attendances", [
            'meeting_number' => 1,
            'meeting_date' => now()->toDateString(),
            'entries' => [['krs_item_id' => $fixture['krsItems'][0]->id, 'status' => 'not-a-real-status']],
        ])
        ->assertApiError(422);
});
