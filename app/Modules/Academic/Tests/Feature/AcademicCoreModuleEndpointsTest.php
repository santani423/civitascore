<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

function grantAcademicCorePermission(User $user, University $university, array $permissionSlugs): void
{
    UserUniversity::query()->create([
        'user_id' => $user->id,
        'university_id' => $university->id,
        'membership_type' => MembershipType::Admin,
        'status' => MembershipStatus::Active,
        'joined_at' => now(),
        'is_default' => true,
    ]);

    $role = Role::factory()->create(['university_id' => $university->id]);

    $permissionIds = collect($permissionSlugs)->map(function (string $slug) {
        [$resource, $action] = explode('.', $slug, 2);

        return Permission::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'resource' => $resource, 'action' => PermissionAction::from($action), 'scope' => PermissionScope::Data],
        )->id;
    });

    $role->permissions()->sync($permissionIds);

    UserRole::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'university_id' => $university->id,
        'assigned_at' => now(),
    ]);
}

test('curriculums/courses/krs-items/grades/attendances all require their own read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantAcademicCorePermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/curriculums')->assertApiError(403);
    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/courses')->assertApiError(403);
    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/krs-items')->assertApiError(403);
    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/grades')->assertApiError(403);
    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/attendances')->assertApiError(403);
});

test('a permitted user can list and view curriculums with their course counts', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $curriculum = Curriculum::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    Course::factory()->count(3)->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'curriculum_id' => $curriculum->id]);

    $otherProgram = StudyProgram::factory()->create(['university_id' => $university->id]);
    Curriculum::factory()->create(['university_id' => $university->id, 'study_program_id' => $otherProgram->id]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantAcademicCorePermission($user, $university, ['curriculums.read']);

    $list = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/curriculums?filter[study_program_id]='.$program->id)
        ->assertApiSuccess();
    expect($list->json('meta.total'))->toBe(1);
    expect($list->json('data.0.courses_count'))->toBe(3);

    $detail = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/curriculums/'.$curriculum->id)
        ->assertApiSuccess();
    expect($detail->json('data.courses'))->toHaveCount(3);
});

test('a permitted user can list courses filtered by curriculum and semester level', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $curriculum = Curriculum::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'curriculum_id' => $curriculum->id, 'semester_level' => 1]);
    Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'curriculum_id' => $curriculum->id, 'semester_level' => 3]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantAcademicCorePermission($user, $university, ['courses.read']);

    $response = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/courses?filter[semester_level]=1')
        ->assertApiSuccess();
    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('data.0.curriculum_name'))->toBe($curriculum->name);
});

test('a permitted user can list krs-items filtered by student and status', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $otherStudent = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $term = AcademicTerm::factory()->create(['university_id' => $university->id, 'is_current' => true]);
    $course = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $classSection = ClassSection::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'academic_term_id' => $term->id, 'course_id' => $course->id]);

    KrsItem::factory()->create(['university_id' => $university->id, 'student_id' => $student->id, 'class_section_id' => $classSection->id, 'academic_term_id' => $term->id]);
    KrsItem::factory()->create(['university_id' => $university->id, 'student_id' => $otherStudent->id, 'class_section_id' => $classSection->id, 'academic_term_id' => $term->id]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantAcademicCorePermission($user, $university, ['krs.read']);

    $response = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/krs-items?filter[student_id]='.$student->id)
        ->assertApiSuccess();
    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('data.0.course_name'))->toBe($course->name);
});

test('a permitted user can list grades filtered by the owning student', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $otherStudent = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $term = AcademicTerm::factory()->create(['university_id' => $university->id, 'is_current' => true]);
    $classSection = ClassSection::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'academic_term_id' => $term->id]);

    $krsItem = KrsItem::factory()->create(['university_id' => $university->id, 'student_id' => $student->id, 'class_section_id' => $classSection->id, 'academic_term_id' => $term->id]);
    $otherKrsItem = KrsItem::factory()->create(['university_id' => $university->id, 'student_id' => $otherStudent->id, 'class_section_id' => $classSection->id, 'academic_term_id' => $term->id]);

    Grade::factory()->create(['university_id' => $university->id, 'krs_item_id' => $krsItem->id, 'letter_grade' => LetterGrade::A]);
    Grade::factory()->create(['university_id' => $university->id, 'krs_item_id' => $otherKrsItem->id, 'letter_grade' => LetterGrade::B]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantAcademicCorePermission($user, $university, ['grades.read']);

    $response = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/grades?filter[student_id]='.$student->id)
        ->assertApiSuccess();
    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('data.0.letter_grade'))->toBe('A');
    expect($response->json('data.0.student_name'))->toBe($student->name);
});

test('a permitted user can list attendances filtered by class section and status', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $student = Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    $term = AcademicTerm::factory()->create(['university_id' => $university->id, 'is_current' => true]);
    $classSectionA = ClassSection::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'academic_term_id' => $term->id]);
    $classSectionB = ClassSection::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'academic_term_id' => $term->id]);

    $krsItemA = KrsItem::factory()->create(['university_id' => $university->id, 'student_id' => $student->id, 'class_section_id' => $classSectionA->id, 'academic_term_id' => $term->id]);
    $krsItemB = KrsItem::factory()->create(['university_id' => $university->id, 'student_id' => $student->id, 'class_section_id' => $classSectionB->id, 'academic_term_id' => $term->id]);

    Attendance::factory()->create(['university_id' => $university->id, 'krs_item_id' => $krsItemA->id, 'meeting_number' => 1, 'status' => AttendanceStatus::Present]);
    Attendance::factory()->create(['university_id' => $university->id, 'krs_item_id' => $krsItemB->id, 'meeting_number' => 1, 'status' => AttendanceStatus::Absent]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantAcademicCorePermission($user, $university, ['attendance.read']);

    $response = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/attendances?filter[class_section_id]='.$classSectionA->id)
        ->assertApiSuccess();
    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('data.0.status'))->toBe('present');
});

test('class-sections list and detail expose the course name/code/credits via the course relation', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $term = AcademicTerm::factory()->create(['university_id' => $university->id, 'is_current' => true]);
    $course = Course::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'name' => 'Kalkulus I', 'credits' => 3]);
    $classSection = ClassSection::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'academic_term_id' => $term->id, 'course_id' => $course->id]);
    Student::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id]);
    KrsItem::factory()->create(['university_id' => $university->id, 'class_section_id' => $classSection->id, 'academic_term_id' => $term->id]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantAcademicCorePermission($user, $university, ['classes.read']);

    $list = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/class-sections')
        ->assertApiSuccess();
    expect($list->json('data.0.course_name'))->toBe('Kalkulus I');
    expect($list->json('data.0.credits'))->toBe(3);
    expect($list->json('data.0.enrolled_count'))->toBe(1);

    $detail = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/class-sections/'.$classSection->id)
        ->assertApiSuccess();
    expect($detail->json('data.course_code'))->toBe($course->code);
});
