<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSchedule;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Lecturer;
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
use Modules\UserManagement\Support\PermissionRegistry;

/*
|--------------------------------------------------------------------------
| Fixture bersama Portal Mahasiswa
|--------------------------------------------------------------------------
|
| Satu "dunia" kecil untuk test layanan mandiri mahasiswa: universitas,
| prodi, kurikulum, semester aktif dengan periode KRS terbuka, dan seorang
| mahasiswa yang login dengan permission persis seperti role `student` di
| OrganizationalRoleSeeder. Nama fungsi berawalan `portal` supaya tidak
| bentrok dengan helper global di file test lain.
|
*/

/**
 * Permission role `student` (OrganizationalRoleSeeder::ROLE_PERMISSIONS['student']).
 *
 * @return array<int, string>
 */
function portalStudentPermissions(): array
{
    return [
        'exam_participation.read', 'exam_participation.create', 'exam_participation.update',
        'student_portal.read', 'student_portal.update',
        'krs_self_service.read', 'krs_self_service.create', 'krs_self_service.update',
        'student_requests.read', 'student_requests.create', 'student_requests.update',
        'assignment_submissions.create',
    ];
}

/**
 * @param  callable(): mixed  $callback
 */
function portalAsTenant(University $university, callable $callback): mixed
{
    app(TenantContext::class)->setUniversityId($university->id);

    try {
        return $callback();
    } finally {
        app(TenantContext::class)->setUniversityId(null);
    }
}

/**
 * @param  array<string, mixed>  $studentAttributes
 * @param  array<string, mixed>  $termAttributes
 * @return array{university: University, program: StudyProgram, curriculum: Curriculum, term: AcademicTerm, student: Student, user: User}
 */
function portalWorld(array $studentAttributes = [], array $termAttributes = [], bool $actingAs = true): array
{
    $university = University::factory()->create(['timezone' => 'Asia/Jakarta']);

    [$program, $curriculum, $term] = portalAsTenant($university, function () use ($university, $termAttributes): array {
        $program = StudyProgram::factory()->create(['university_id' => $university->id, 'name' => 'Sistem Informasi']);
        $curriculum = Curriculum::factory()->create(['university_id' => $university->id, 'study_program_id' => $program->id, 'is_active' => true]);
        $term = AcademicTerm::factory()->create([
            'university_id' => $university->id,
            'academic_year' => '2026/2027',
            'semester' => AcademicSemester::Ganjil,
            'is_current' => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'krs_start_date' => now()->subWeek()->toDateString(),
            'krs_end_date' => now()->addWeek()->toDateString(),
            ...$termAttributes,
        ]);

        return [$program, $curriculum, $term];
    });

    $user = $actingAs
        ? actingAsUserWithUniversityPermissions($university, portalStudentPermissions())
        : User::factory()->create();

    $student = portalAsTenant($university, fn () => Student::factory()->create([
        'university_id' => $university->id,
        'study_program_id' => $program->id,
        'user_id' => $user->id,
        'admission_year' => 2024,
        'status' => StudentStatus::Active,
        ...$studentAttributes,
    ]));

    return compact('university', 'program', 'curriculum', 'term', 'student', 'user');
}

/**
 * Mahasiswa lain di universitas & prodi yang sama (tidak login).
 *
 * @param  array<string, mixed>  $world
 */
function portalOtherStudent(array $world, array $attributes = []): Student
{
    return portalAsTenant($world['university'], fn () => Student::factory()->create([
        'university_id' => $world['university']->id,
        'study_program_id' => $world['program']->id,
        'admission_year' => 2024,
        'status' => StudentStatus::Active,
        ...$attributes,
    ]));
}

/**
 * @param  array<string, mixed>  $world
 * @param  array<string, mixed>  $attributes
 */
function portalCourse(array $world, array $attributes = []): Course
{
    return portalAsTenant($world['university'], fn () => Course::factory()->create([
        'university_id' => $world['university']->id,
        'study_program_id' => $world['program']->id,
        'curriculum_id' => $world['curriculum']->id,
        'credits' => 3,
        ...$attributes,
    ]));
}

/**
 * Kelas di semester tertentu (default semester aktif) beserta jadwal
 * mingguannya: [[hari ISO, "08:00", "09:40", "R.101"], ...].
 *
 * @param  array<string, mixed>  $world
 * @param  array<int, array{0: int, 1: string, 2: string, 3?: string}>  $schedules
 * @param  array<string, mixed>  $attributes
 */
function portalClass(array $world, Course $course, array $schedules = [], array $attributes = [], ?AcademicTerm $term = null): ClassSection
{
    return portalAsTenant($world['university'], function () use ($world, $course, $schedules, $attributes, $term): ClassSection {
        $classSection = ClassSection::factory()->create([
            'university_id' => $world['university']->id,
            'study_program_id' => $world['program']->id,
            'academic_term_id' => ($term ?? $world['term'])->id,
            'course_id' => $course->id,
            'capacity' => 40,
            ...$attributes,
        ]);

        foreach ($schedules as $schedule) {
            ClassSchedule::query()->create([
                'university_id' => $world['university']->id,
                'class_section_id' => $classSection->id,
                'day_of_week' => $schedule[0],
                'start_time' => $schedule[1],
                'end_time' => $schedule[2],
                'room' => $schedule[3] ?? 'R.101',
            ]);
        }

        return $classSection;
    });
}

/**
 * @param  array<string, mixed>  $world
 */
function portalPastTerm(array $world, string $academicYear, AcademicSemester $semester, string $startDate): AcademicTerm
{
    return portalAsTenant($world['university'], fn () => AcademicTerm::factory()->create([
        'university_id' => $world['university']->id,
        'academic_year' => $academicYear,
        'semester' => $semester,
        'is_current' => false,
        'start_date' => $startDate,
        'end_date' => CarbonImmutable::parse($startDate)->addMonths(5)->toDateString(),
    ]));
}

/**
 * KRS Enrolled + nilai pada semester tertentu (riwayat studi).
 *
 * @param  array<string, mixed>  $world
 */
function portalGraded(array $world, Student $student, AcademicTerm $term, Course $course, LetterGrade $grade, ?float $score = null): KrsItem
{
    $classSection = portalClass($world, $course, [], [], $term);

    return portalAsTenant($world['university'], function () use ($world, $student, $term, $classSection, $grade, $score): KrsItem {
        $item = KrsItem::factory()->create([
            'university_id' => $world['university']->id,
            'student_id' => $student->id,
            'class_section_id' => $classSection->id,
            'academic_term_id' => $term->id,
            'status' => KrsItemStatus::Enrolled,
        ]);

        Grade::factory()->create([
            'university_id' => $world['university']->id,
            'krs_item_id' => $item->id,
            'letter_grade' => $grade,
            'score' => $score ?? 80,
        ]);

        return $item;
    });
}

/**
 * Baris KRS langsung (tanpa lewat portal) — mis. kursi milik mahasiswa lain.
 *
 * @param  array<string, mixed>  $world
 */
function portalEnroll(array $world, Student $student, ClassSection $classSection, KrsItemStatus $status = KrsItemStatus::Enrolled): KrsItem
{
    return portalAsTenant($world['university'], fn () => KrsItem::factory()->create([
        'university_id' => $world['university']->id,
        'student_id' => $student->id,
        'class_section_id' => $classSection->id,
        'academic_term_id' => $classSection->academic_term_id,
        'status' => $status,
    ]));
}

/**
 * Dosen dengan akun login (identity link users ← employees.user_id ←
 * lecturers.employee_id), tanpa login-kan.
 *
 * @param  array<string, mixed>  $world
 * @return array{lecturer: Lecturer, user: User}
 */
function portalLecturer(array $world, ?User $user = null): array
{
    $user ??= User::factory()->create();

    $lecturer = portalAsTenant($world['university'], function () use ($world, $user): Lecturer {
        $employee = Employee::factory()->create(['university_id' => $world['university']->id, 'user_id' => $user->id]);

        return Lecturer::factory()->create(['university_id' => $world['university']->id, 'employee_id' => $employee->id]);
    });

    return ['lecturer' => $lecturer, 'user' => $user];
}

/**
 * Memberi user yang sudah ada keanggotaan universitas + role berisi
 * permission tertentu (mis. dosen wali: krs_advising.*), tanpa login-kan.
 *
 * @param  array<string, mixed>  $world
 * @param  array<int, string>  $permissionSlugs
 */
function portalGrant(array $world, User $user, array $permissionSlugs, MembershipType $membership = MembershipType::Lecturer): void
{
    $university = $world['university'];

    UserUniversity::query()->firstOrCreate(
        ['user_id' => $user->id, 'university_id' => $university->id],
        ['membership_type' => $membership, 'status' => MembershipStatus::Active, 'joined_at' => now(), 'is_default' => true],
    );

    $role = Role::factory()->create(['university_id' => $university->id]);
    $role->permissions()->sync(collect($permissionSlugs)->map(function (string $slug): string {
        [$resource, $action] = explode('.', $slug, 2);

        return Permission::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'resource' => $resource, 'action' => PermissionAction::from($action), 'scope' => PermissionScope::Data],
        )->id;
    })->all());

    UserRole::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'university_id' => $university->id,
        'assigned_at' => now(),
    ]);

    PermissionRegistry::flushAll();
}

/**
 * @param  array<string, mixed>  $world
 * @return array<string, string>
 */
function portalHeaders(array $world): array
{
    return ['X-University-ID' => $world['university']->id];
}
