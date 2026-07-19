<?php

namespace Modules\Academic\Database\Seeders;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
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

    /**
     * @param  array{faculties: int, study_programs: int, students: int, lecturers: int, employees: int, classes: int}  $scale
     * @return array{students: Collection<int, Student>, current_term: AcademicTerm}
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

        $this->seedStudents($university, $studyPrograms, $scale['students']);
        $this->seedLecturers($university, $faculties, $scale['lecturers']);
        $this->seedEmployees($university, $scale['employees']);
        $this->seedClassSections($university, $studyPrograms, $currentTerm, $scale['classes']);

        return [
            'students' => Student::query()->get(),
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
     */
    private function seedClassSections(University $university, Collection $studyPrograms, AcademicTerm $currentTerm, int $count): void
    {
        $programIds = $studyPrograms->pluck('id')->all();
        $now = now();
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'study_program_id' => $programIds[array_rand($programIds)],
                'academic_term_id' => $currentTerm->id,
                'course_name' => fake()->words(3, true),
                'class_code' => 'K'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'capacity' => fake()->numberBetween(25, 50),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('class_sections')->insert($rows);
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
