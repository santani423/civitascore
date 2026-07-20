<?php

namespace Modules\Academic\Database\Seeders;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

/**
 * Builds one university's academic org structure + people at a configurable
 * scale (faculties/study_programs/students/lecturers/employees/classes) —
 * used by DemoUniversitiesSeeder to give each demo university a different,
 * realistic size (see docs on UniversitySeeder for why UND/ITM/STIKes are
 * deliberately different tiers).
 *
 * Bulk tables (students/lecturers/employees/class_sections) are inserted
 * via raw DB::table()->insert() in chunks rather than Eloquent::create() —
 * at hundreds of rows per university this is meaningfully faster and avoids
 * any per-row event/cast overhead; university_id is set explicitly on every
 * row regardless (DatabaseSeeder's WithoutModelEvents disables
 * TenantScoped's auto-fill anyway, so this was required either way).
 *
 * Idempotent: if this university already has students seeded, skips
 * regeneration entirely and returns the existing rows instead — safe to
 * re-run `php artisan db:seed` without duplicating thousands of rows.
 */
class AcademicSeeder extends Seeder
{
    private const FACULTY_POOL = [
        'Ekonomi dan Bisnis', 'Teknik', 'Kedokteran', 'Hukum', 'Ilmu Komunikasi',
        'Psikologi', 'Ilmu Komputer', 'Pertanian', 'Keguruan dan Ilmu Pendidikan',
    ];

    private const STUDY_PROGRAM_POOL = [
        ['name' => 'Manajemen', 'degree' => 'S1'],
        ['name' => 'Akuntansi', 'degree' => 'S1'],
        ['name' => 'Teknik Informatika', 'degree' => 'S1'],
        ['name' => 'Sistem Informasi', 'degree' => 'S1'],
        ['name' => 'Teknik Sipil', 'degree' => 'S1'],
        ['name' => 'Teknik Elektro', 'degree' => 'S1'],
        ['name' => 'Ilmu Hukum', 'degree' => 'S1'],
        ['name' => 'Pendidikan Dokter', 'degree' => 'S1'],
        ['name' => 'Psikologi', 'degree' => 'S1'],
        ['name' => 'Ilmu Komunikasi', 'degree' => 'S1'],
        ['name' => 'Agroteknologi', 'degree' => 'S1'],
        ['name' => 'Farmasi', 'degree' => 'S1'],
        ['name' => 'Keperawatan', 'degree' => 'S1'],
        ['name' => 'Kesehatan Masyarakat', 'degree' => 'S1'],
        ['name' => 'Hubungan Internasional', 'degree' => 'S1'],
        ['name' => 'Bisnis Digital', 'degree' => 'S1'],
        ['name' => 'Teknik Industri', 'degree' => 'S1'],
        ['name' => 'Desain Komunikasi Visual', 'degree' => 'S1'],
        ['name' => 'Manajemen', 'degree' => 'S2'],
        ['name' => 'Ilmu Hukum', 'degree' => 'S2'],
    ];

    private const UNIT_KERJA_POOL = ['Keuangan', 'SDM', 'Perpustakaan', 'Umum', 'Teknologi Informasi'];

    /** Admission-year weights, biased toward recent years so the growth chart trends up. */
    private const ADMISSION_YEAR_WEIGHTS = [2022 => 10, 2023 => 15, 2024 => 20, 2025 => 25, 2026 => 30];

    /** @var array<string, int> status-slug => weight out of 100 */
    private const STATUS_WEIGHTS = [
        'active' => 90,
        'leave' => 4,
        'graduated' => 3,
        'inactive' => 2,
        'dropped_out' => 1,
    ];

    /** Courses generated per study program (plan calls for "~15-20/prodi"). */
    private const COURSES_PER_STUDY_PROGRAM_MIN = 15;

    private const COURSES_PER_STUDY_PROGRAM_MAX = 20;

    /** How many of a student's own-program classes they're enrolled in (KRS). */
    private const KRS_CLASSES_PER_STUDENT_MIN = 4;

    private const KRS_CLASSES_PER_STUDENT_MAX = 6;

    /** Share of KRS entries (out of 100) that get a grade / a seeded attendance history. */
    private const GRADE_SEED_CHANCE = 40;

    private const ATTENDANCE_SEED_CHANCE = 30;

    private const ATTENDANCE_MEETINGS = 6;

    /** @var array<string, int> letter-grade => weight out of 100, biased toward passing grades. */
    private const LETTER_GRADE_WEIGHTS = ['A' => 15, 'AB' => 20, 'B' => 25, 'BC' => 15, 'C' => 15, 'D' => 5, 'E' => 5];

    /** @var array<string, int> attendance-status => weight out of 100 */
    private const ATTENDANCE_STATUS_WEIGHTS = ['present' => 75, 'permitted' => 10, 'sick' => 8, 'absent' => 7];

    /**
     * @param  array{faculties: int, study_programs: int, students: int, lecturers: int, employees: int, classes: int}  $scale
     * @return array{students: \Illuminate\Database\Eloquent\Collection<int, Student>, current_term: AcademicTerm}
     */
    public function run(University $university, array $scale): array
    {
        app(TenantContext::class)->setUniversityId($university->id);

        $currentTerm = $this->seedAcademicTerms($university);

        if (Student::query()->count() > 0) {
            return [
                'students' => Student::query()->get(),
                'current_term' => $currentTerm,
            ];
        }

        $faculties = $this->seedFaculties($university, $scale['faculties']);
        $studyPrograms = $this->seedStudyPrograms($university, $faculties, $scale['study_programs']);
        $curriculumsByProgram = $this->seedCurriculums($university, $studyPrograms);
        $coursesByProgram = $this->seedCourses($university, $studyPrograms, $curriculumsByProgram);

        $this->seedStudents($university, $studyPrograms, $scale['students']);
        $this->seedLecturers($university, $faculties, $scale['lecturers']);
        $this->seedEmployees($university, $scale['employees']);

        $students = Student::query()->get();
        $classSectionsByProgram = $this->seedClassSections($university, $studyPrograms, $coursesByProgram, $currentTerm, $scale['classes']);
        $this->seedKrsGradesAttendance($university, $students, $classSectionsByProgram, $currentTerm);

        return [
            'students' => $students,
            'current_term' => $currentTerm,
        ];
    }

    private function seedAcademicTerms(University $university): AcademicTerm
    {
        AcademicTerm::query()->updateOrCreate(
            ['university_id' => $university->id, 'academic_year' => '2025/2026', 'semester' => AcademicSemester::Genap],
            ['is_current' => false, 'start_date' => '2026-02-01', 'end_date' => '2026-07-31'],
        );

        return AcademicTerm::query()->updateOrCreate(
            ['university_id' => $university->id, 'academic_year' => '2026/2027', 'semester' => AcademicSemester::Ganjil],
            ['is_current' => true, 'start_date' => '2026-08-01', 'end_date' => '2027-01-31'],
        );
    }

    /**
     * @return Collection<int, Faculty>
     */
    private function seedFaculties(University $university, int $count): Collection
    {
        $names = array_slice(self::FACULTY_POOL, 0, $count);

        return collect($names)->map(fn (string $name) => Faculty::query()->updateOrCreate(
            ['university_id' => $university->id, 'code' => Str::upper(Str::substr(Str::slug($name, ''), 0, 4))],
            ['name' => "Fakultas {$name}", 'is_active' => true],
        ));
    }

    /**
     * @param  Collection<int, Faculty>  $faculties
     * @return Collection<int, StudyProgram>
     */
    private function seedStudyPrograms(University $university, Collection $faculties, int $count): Collection
    {
        $facultyList = $faculties->values();
        $created = collect();

        for ($i = 0; $i < $count; $i++) {
            $definition = self::STUDY_PROGRAM_POOL[$i % count(self::STUDY_PROGRAM_POOL)];
            $faculty = $facultyList[$i % $facultyList->count()];
            $code = Str::upper(Str::substr(Str::slug($definition['name'], ''), 0, 3)).($i + 1);

            $created->push(StudyProgram::query()->updateOrCreate(
                ['university_id' => $university->id, 'code' => $code],
                [
                    'faculty_id' => $faculty->id,
                    'name' => $definition['name'],
                    'degree_level' => $definition['degree'],
                    'is_active' => true,
                ],
            ));
        }

        return $created;
    }

    /**
     * @param  Collection<int, StudyProgram>  $studyPrograms
     */
    private function seedStudents(University $university, Collection $studyPrograms, int $count): void
    {
        $programIds = $studyPrograms->pluck('id')->all();
        $now = now();
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $admissionYear = $this->weightedPick(self::ADMISSION_YEAR_WEIGHTS);
            $status = StudentStatus::from($this->weightedPick(self::STATUS_WEIGHTS));

            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'study_program_id' => $programIds[array_rand($programIds)],
                'nim' => "{$admissionYear}".str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'name' => fake()->name(),
                'email' => "mahasiswa{$i}.".Str::slug($university->code).'@student.test',
                'admission_year' => $admissionYear,
                'status' => $status->value,
                'enrolled_at' => "{$admissionYear}-08-01",
                'graduated_at' => $status === StudentStatus::Graduated ? sprintf('%d-06-30', $admissionYear + 4) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('students')->insert($chunk);
        }
    }

    /**
     * @param  Collection<int, Faculty>  $faculties
     */
    private function seedLecturers(University $university, Collection $faculties, int $count): void
    {
        $facultyIds = $faculties->pluck('id')->all();
        $now = now();
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'faculty_id' => $facultyIds[array_rand($facultyIds)],
                'nidn' => Str::upper($university->code).str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'name' => 'Dr. '.fake()->name(),
                'email' => "dosen{$i}.".Str::slug($university->code).'@lecturer.test',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('lecturers')->insert($rows);
    }

    private function seedEmployees(University $university, int $count): void
    {
        $now = now();
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'unit_kerja' => self::UNIT_KERJA_POOL[$i % count(self::UNIT_KERJA_POOL)],
                'name' => fake()->name(),
                'email' => "pegawai{$i}.".Str::slug($university->code).'@staff.test',
                'position' => fake()->jobTitle(),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('employees')->insert($rows);
    }

    /**
     * @param  Collection<int, StudyProgram>  $studyPrograms
     * @return Collection<string, Curriculum>
     */
    private function seedCurriculums(University $university, Collection $studyPrograms): Collection
    {
        return $studyPrograms->mapWithKeys(fn (StudyProgram $studyProgram) => [
            $studyProgram->id => Curriculum::query()->updateOrCreate(
                ['university_id' => $university->id, 'study_program_id' => $studyProgram->id, 'name' => "Kurikulum {$studyProgram->code}"],
                ['academic_year' => '2026/2027', 'is_active' => true],
            ),
        ]);
    }

    /**
     * @param  Collection<int, StudyProgram>  $studyPrograms
     * @param  Collection<string, Curriculum>  $curriculumsByProgram
     * @return Collection<string, \Illuminate\Database\Eloquent\Collection<int, Course>>  courses grouped by study_program_id
     */
    private function seedCourses(University $university, Collection $studyPrograms, Collection $curriculumsByProgram): Collection
    {
        $now = now();
        $rows = [];
        $globalIndex = 0;

        foreach ($studyPrograms as $studyProgram) {
            $curriculum = $curriculumsByProgram[$studyProgram->id];
            $prefix = Str::upper(Str::substr(Str::slug($studyProgram->name, ''), 0, 3));
            $courseCount = fake()->numberBetween(self::COURSES_PER_STUDY_PROGRAM_MIN, self::COURSES_PER_STUDY_PROGRAM_MAX);

            for ($j = 1; $j <= $courseCount; $j++) {
                $globalIndex++;

                $rows[] = [
                    'id' => (string) Str::ulid(),
                    'university_id' => $university->id,
                    'study_program_id' => $studyProgram->id,
                    'curriculum_id' => $curriculum->id,
                    'code' => $prefix.str_pad((string) $globalIndex, 4, '0', STR_PAD_LEFT),
                    'name' => Str::title(fake()->words(3, true)),
                    'credits' => fake()->numberBetween(2, 4),
                    'semester_level' => min(8, (int) ceil($j / ($courseCount / 8))),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('courses')->insert($chunk);
        }

        return Course::query()->get()->groupBy('study_program_id');
    }

    /**
     * @param  Collection<int, StudyProgram>  $studyPrograms
     * @param  Collection<string, \Illuminate\Database\Eloquent\Collection<int, Course>>  $coursesByProgram
     * @return Collection<string, \Illuminate\Database\Eloquent\Collection<int, ClassSection>>  class sections grouped by study_program_id
     */
    private function seedClassSections(University $university, Collection $studyPrograms, Collection $coursesByProgram, AcademicTerm $currentTerm, int $count): Collection
    {
        $programList = $studyPrograms->values();
        $now = now();
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            // Round-robin (not random) study program assignment so every
            // program is guaranteed at least one class section — KRS
            // seeding below needs each active student's own program to have
            // classes to enroll them in.
            $studyProgram = $programList[($i - 1) % $programList->count()];
            $programCourses = $coursesByProgram->get($studyProgram->id, collect());

            if ($programCourses->isEmpty()) {
                continue;
            }

            $course = $programCourses->random();

            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'study_program_id' => $studyProgram->id,
                'academic_term_id' => $currentTerm->id,
                'course_id' => $course->id,
                'class_code' => 'K'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'capacity' => fake()->numberBetween(25, 50),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('class_sections')->insert($chunk);
        }

        return ClassSection::query()->get()->groupBy('study_program_id');
    }

    /**
     * Enrolls every active student in 4-6 class sections from their own
     * study program (KRS), then seeds a grade for ~40% of KRS entries and a
     * 6-meeting attendance history for ~30% — both intentionally partial so
     * "belum dinilai" / empty-attendance states are exercised too, not just
     * the happy path.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Student>  $students
     * @param  Collection<string, \Illuminate\Database\Eloquent\Collection<int, ClassSection>>  $classSectionsByProgram
     */
    private function seedKrsGradesAttendance(
        University $university,
        \Illuminate\Database\Eloquent\Collection $students,
        Collection $classSectionsByProgram,
        AcademicTerm $currentTerm,
    ): void {
        $now = now();
        $krsRows = [];
        $gradeRows = [];
        $attendanceRows = [];

        $activeStudents = $students->filter(fn (Student $student) => $student->status === StudentStatus::Active);

        foreach ($activeStudents as $student) {
            $programClasses = $classSectionsByProgram->get($student->study_program_id, collect());

            if ($programClasses->isEmpty()) {
                continue;
            }

            $classCount = min($programClasses->count(), random_int(self::KRS_CLASSES_PER_STUDENT_MIN, self::KRS_CLASSES_PER_STUDENT_MAX));

            foreach ($programClasses->shuffle()->take($classCount) as $classSection) {
                $krsItemId = (string) Str::ulid();

                $krsRows[] = [
                    'id' => $krsItemId,
                    'university_id' => $university->id,
                    'student_id' => $student->id,
                    'class_section_id' => $classSection->id,
                    'academic_term_id' => $currentTerm->id,
                    'status' => KrsItemStatus::Enrolled->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (random_int(1, 100) <= self::GRADE_SEED_CHANCE) {
                    $gradeRows[] = [
                        'id' => (string) Str::ulid(),
                        'university_id' => $university->id,
                        'krs_item_id' => $krsItemId,
                        'letter_grade' => LetterGrade::from($this->weightedPick(self::LETTER_GRADE_WEIGHTS))->value,
                        'score' => fake()->randomFloat(2, 40, 100),
                        'submitted_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (random_int(1, 100) <= self::ATTENDANCE_SEED_CHANCE) {
                    foreach (range(1, self::ATTENDANCE_MEETINGS) as $meetingNumber) {
                        $attendanceRows[] = [
                            'id' => (string) Str::ulid(),
                            'university_id' => $university->id,
                            'krs_item_id' => $krsItemId,
                            'meeting_number' => $meetingNumber,
                            'meeting_date' => now()->subWeeks(self::ATTENDANCE_MEETINGS - $meetingNumber)->toDateString(),
                            'status' => AttendanceStatus::from($this->weightedPick(self::ATTENDANCE_STATUS_WEIGHTS))->value,
                            'notes' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        foreach (array_chunk($krsRows, 500) as $chunk) {
            DB::table('krs_items')->insert($chunk);
        }

        foreach (array_chunk($gradeRows, 500) as $chunk) {
            DB::table('grades')->insert($chunk);
        }

        foreach (array_chunk($attendanceRows, 500) as $chunk) {
            DB::table('attendances')->insert($chunk);
        }
    }

    /**
     * @param  array<int|string, int>  $weights
     */
    private function weightedPick(array $weights): int|string
    {
        $total = array_sum($weights);
        $random = random_int(1, $total);
        $cumulative = 0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;

            if ($random <= $cumulative) {
                return $key;
            }
        }

        return array_key_last($weights);
    }
}
