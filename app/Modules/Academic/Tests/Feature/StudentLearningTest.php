<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Models\Assignment;
use Modules\Academic\Models\AssignmentSubmission;
use Modules\Academic\Models\CourseMaterial;

/*
| Perkuliahan: materi per pertemuan, tugas, pengumpulan, penilaian — dosen
| pengampu mengelola kelasnya sendiri, mahasiswa hanya melihat kelas yang
| diikutinya (KRS Enrolled). Berkas lewat FileManagement + RestrictsFileAccess.
*/

beforeEach(function () {
    Storage::fake('local');
});

/**
 * @param  array<string, mixed>  $world
 */
function learningUpload(array $world, string $name = 'tugas.pdf', int $kilobytes = 200, string $mime = 'application/pdf'): string
{
    return test()->withHeaders(portalHeaders($world))
        ->post('/api/v1/file-uploads', ['file' => UploadedFile::fake()->create($name, $kilobytes, $mime)], ['Accept' => 'application/json'])
        ->assertApiSuccess(201)
        ->json('data.id');
}

/**
 * Dunia + dosen pengampu (login-able) + satu kelas yang diikuti mahasiswa.
 *
 * @return array<string, mixed>
 */
function learningWorld(): array
{
    $world = portalWorld();
    $lecturer = portalLecturer($world);
    $classSection = portalClass($world, portalCourse($world, ['name' => 'Basis Data']), [[3, '08:00', '10:00']], ['lecturer_id' => $lecturer['lecturer']->id]);
    portalEnroll($world, $world['student'], $classSection);
    portalGrant($world, $lecturer['user'], [
        'course_materials.read', 'course_materials.create', 'course_materials.update', 'course_materials.delete',
        'assignments.read', 'assignments.create', 'assignments.update', 'assignments.delete',
        'assignment_submissions.read', 'assignment_submissions.update',
    ]);

    return [...$world, 'lecturer' => $lecturer, 'class' => $classSection];
}

test('a lecturer publishes materials and an assignment for their own class and the student sees them', function () {
    $world = learningWorld();

    $this->actingAs($world['lecturer']['user']);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/teaching/classes')
        ->assertApiSuccess()
        ->assertJsonPath('data.0.id', $world['class']->id)
        ->assertJsonPath('data.0.enrolled_count', 1);

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/class-sections/{$world['class']->id}/materials", [
        'title' => 'Normalisasi Basis Data', 'type' => 'link', 'url' => 'https://example.com/normalisasi', 'meeting_number' => 3,
    ])->assertApiSuccess(201);

    $fileId = learningUpload($world, 'modul.pdf');
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/class-sections/{$world['class']->id}/materials", [
        'title' => 'Modul Pertemuan 1', 'type' => 'file', 'file_upload_id' => $fileId, 'meeting_number' => 1,
    ])->assertApiSuccess(201);

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/class-sections/{$world['class']->id}/assignments", [
        'title' => 'ERD Toko Online', 'description' => 'Buat ERD lengkap.', 'due_at' => now()->addDays(3)->toIso8601String(),
    ])->assertApiSuccess(201);

    $this->actingAs($world['user']);
    $course = $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/courses/{$world['class']->id}")->assertApiSuccess();

    expect(collect($course->json('data.meetings'))->pluck('label')->all())->toBe(['Pertemuan 1', 'Pertemuan 3'])
        ->and($course->json('data.assignments.0.title'))->toBe('ERD Toko Online')
        ->and($course->json('data.assignments.0.status'))->toBe('not_submitted')
        ->and($world['user']->notifications()->where('data->event_key', 'student.assignment_published')->exists())->toBeTrue();

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/courses')
        ->assertJsonPath('data.courses.0.materials_count', 2)
        ->assertJsonPath('data.courses.0.pending_assignments_count', 1);

    // Mahasiswa peserta kelas boleh mengunduh berkas materi.
    $this->withHeaders(portalHeaders($world))->get("/api/v1/file-uploads/{$fileId}/download")->assertOk();
});

test('a lecturer cannot manage classes they do not teach, while the academic office can', function () {
    $world = learningWorld();
    $other = portalLecturer($world);
    portalGrant($world, $other['user'], ['course_materials.read', 'course_materials.create', 'assignments.read', 'assignments.create']);

    $this->actingAs($other['user']);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/class-sections/{$world['class']->id}/materials", [
        'title' => 'Materi Penyusup', 'type' => 'text', 'description' => 'x',
    ])->assertApiError(403);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/teaching/classes')->assertJsonCount(0, 'data');

    actingAsUserWithUniversityPermissions($world['university'], ['classes.update', 'course_materials.create']);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/class-sections/{$world['class']->id}/materials", [
        'title' => 'Materi Resmi', 'type' => 'text', 'description' => 'Isi materi',
    ])->assertApiSuccess(201);
});

test('students only see published materials and assignments of classes they are enrolled in', function () {
    $world = learningWorld();
    $foreignClass = portalClass($world, portalCourse($world));
    $hidden = portalAsTenant($world['university'], fn () => Assignment::factory()->create([
        'university_id' => $world['university']->id, 'class_section_id' => $world['class']->id, 'is_published' => false,
    ]));
    $foreignAssignment = portalAsTenant($world['university'], fn () => Assignment::factory()->create([
        'university_id' => $world['university']->id, 'class_section_id' => $foreignClass->id,
    ]));
    portalAsTenant($world['university'], fn () => CourseMaterial::factory()->create([
        'university_id' => $world['university']->id, 'class_section_id' => $world['class']->id, 'is_published' => false,
    ]));

    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/courses/{$foreignClass->id}")->assertApiError(404);
    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/assignments/{$hidden->id}")->assertApiError(404);
    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/assignments/{$foreignAssignment->id}")->assertApiError(404);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$foreignAssignment->id}/submission", ['notes' => 'x'])->assertApiError(404);

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/assignments')->assertJsonCount(0, 'data.assignments');
    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/courses/{$world['class']->id}")->assertJsonCount(0, 'data.meetings');
});

test('a student submits, replaces and gets graded on an assignment with file rules enforced', function () {
    $world = learningWorld();
    $assignment = portalAsTenant($world['university'], fn () => Assignment::factory()->create([
        'university_id' => $world['university']->id, 'class_section_id' => $world['class']->id,
        'title' => 'Laporan Praktikum', 'allowed_extensions' => ['pdf'], 'max_file_size_mb' => 1, 'max_score' => 100,
        'allow_resubmission' => true, 'due_at' => now()->addDay(),
    ]));

    // Format & ukuran mengikuti aturan tugas, bukan hanya aturan unggah umum.
    $docx = learningUpload($world, 'laporan.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$assignment->id}/submission", ['file_upload_id' => $docx])
        ->assertApiError(422)
        ->assertJsonPath('errors.file_upload_id.0', 'Format berkas tidak diizinkan. Format yang diterima: pdf.');

    $big = learningUpload($world, 'besar.pdf', 2048);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$assignment->id}/submission", ['file_upload_id' => $big])
        ->assertApiError(422)
        ->assertJsonPath('errors.file_upload_id.0', 'Ukuran berkas melebihi batas 1 MB.');

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$assignment->id}/submission", [])->assertApiError(422);

    $first = learningUpload($world, 'v1.pdf');
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$assignment->id}/submission", ['file_upload_id' => $first])
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.submission.submission_count', 1);

    $second = learningUpload($world, 'v2.pdf');
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$assignment->id}/submission", ['file_upload_id' => $second, 'notes' => 'Revisi'])
        ->assertJsonPath('data.submission.submission_count', 2)
        ->assertJsonPath('data.submission.file.name', 'v2.pdf');

    $submission = AssignmentSubmission::query()->withoutGlobalScopes()->sole();

    // Mahasiswa lain tidak bisa mengunduh berkas pengumpulan ini.
    $intruder = portalOtherStudent($world);
    $intruderUser = actingAsUserWithUniversityPermissions($world['university'], portalStudentPermissions());
    portalAsTenant($world['university'], fn () => $intruder->update(['user_id' => $intruderUser->id]));
    $this->withHeaders(portalHeaders($world))->get("/api/v1/file-uploads/{$second}/download", ['Accept' => 'application/json'])->assertApiError(403);

    // Dosen pengampu menilai.
    $this->actingAs($world['lecturer']['user']);
    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/assignments/{$assignment->id}/submissions")
        ->assertApiSuccess()
        ->assertJsonPath('data.submissions.0.status', 'submitted');
    $this->withHeaders(portalHeaders($world))->get("/api/v1/file-uploads/{$second}/download")->assertOk();
    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/assignment-submissions/{$submission->id}/grade", ['score' => 150])->assertApiError(422);
    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/assignment-submissions/{$submission->id}/grade", ['score' => 88, 'feedback' => 'Bagus'])
        ->assertApiSuccess();

    $this->actingAs($world['user']);
    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/assignments/{$assignment->id}")
        ->assertJsonPath('data.status', 'graded')
        ->assertJsonPath('data.submission.score', '88.00')
        ->assertJsonPath('data.submission.feedback', 'Bagus')
        ->assertJsonPath('data.can_submit', false);
    expect($world['user']->notifications()->where('data->event_key', 'student.assignment_graded')->exists())->toBeTrue();

    $third = learningUpload($world, 'v3.pdf');
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$assignment->id}/submission", ['file_upload_id' => $third])
        ->assertApiError(409);
});

test('late submissions are refused unless the assignment allows them, and are then flagged late', function () {
    $world = learningWorld();
    [$strict, $lenient] = portalAsTenant($world['university'], fn () => [
        Assignment::factory()->create(['university_id' => $world['university']->id, 'class_section_id' => $world['class']->id, 'due_at' => now()->subHour(), 'allow_late_submission' => false]),
        Assignment::factory()->create(['university_id' => $world['university']->id, 'class_section_id' => $world['class']->id, 'due_at' => now()->subHour(), 'allow_late_submission' => true]),
    ]);

    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/assignments/{$strict->id}")->assertJsonPath('data.status', 'missed');
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$strict->id}/submission", ['notes' => 'Maaf terlambat'])
        ->assertApiError(409)
        ->assertJsonPath('message', 'Batas waktu pengumpulan tugas sudah lewat.');

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$lenient->id}/submission", ['notes' => 'Jawaban saya'])
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'late')
        ->assertJsonPath('data.submission.is_late', true);
});

test('a student cannot attach a file uploaded by someone else', function () {
    $world = learningWorld();
    $assignment = portalAsTenant($world['university'], fn () => Assignment::factory()->create(['university_id' => $world['university']->id, 'class_section_id' => $world['class']->id, 'allowed_extensions' => null]));

    $this->actingAs($world['lecturer']['user']);
    $lecturerFile = learningUpload($world, 'kunci.pdf');

    $this->actingAs($world['user']);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$assignment->id}/submission", ['file_upload_id' => $lecturerFile])
        ->assertApiError(422)
        ->assertJsonPath('errors.file_upload_id.0', 'Berkas tidak valid atau tidak dapat dilampirkan.');
});

test('assignments with submissions cannot be deleted', function () {
    $world = learningWorld();
    $assignment = portalAsTenant($world['university'], fn () => Assignment::factory()->create(['university_id' => $world['university']->id, 'class_section_id' => $world['class']->id]));
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/assignments/{$assignment->id}/submission", ['notes' => 'Jawaban'])->assertApiSuccess();

    $this->actingAs($world['lecturer']['user']);
    $this->withHeaders(portalHeaders($world))->deleteJson("/api/v1/assignments/{$assignment->id}")->assertApiError(409);
});
