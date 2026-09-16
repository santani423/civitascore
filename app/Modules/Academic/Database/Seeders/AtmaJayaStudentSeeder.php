<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

/**
 * Seeds the real Teknik Informatika student roster for Universitas Katolik
 * Indonesia Atma Jaya (university code UAJ) — unlike AcademicSeeder's
 * faker-generated students, every row here is an actual mahasiswa
 * (nim/name/tanggal_lahir supplied by the user), not sample data.
 * admission_year is read off the nim's embedded year segment (e.g.
 * "12025006231" -> 2025) rather than assigned randomly.
 *
 * Also seeds the Teknik Informatika curriculum's mata kuliah (COURSE_CATALOG
 * below) so the study program isn't left without courses — AcademicSeeder
 * is never run for this university (see DemoUniversitiesSeeder's UAJ
 * academic_scale, all zeroed out). There's one kelas — "TI01" — per
 * university, but since a class_section is 1:1 with a course in this
 * schema, "one kelas offering every mata kuliah" means one TI01 row per
 * course (all sharing the same class_code); every roster student is
 * enrolled in all of them, so picking any mata kuliah in the Ujian form
 * resolves to the same cohort of real participants.
 *
 * Idempotent: Faculty/StudyProgram/Curriculum are updateOrCreate by code
 * (or name), Course by (university_id, code) — matching its unique
 * constraint — Student by (university_id, nim) — matching the students
 * table's unique constraint — ClassSection by (university_id, course_id,
 * academic_term_id), and KrsItem by (student_id, class_section_id) —
 * matching its unique constraint — so re-running never duplicates rows.
 */
class AtmaJayaStudentSeeder extends Seeder
{
    /** @var array<int, array{nim: string, name: string, tanggal_lahir: string}> */
    private const ROSTER = [
        ['nim' => '12025006231', 'name' => 'ADHE TRIMADHANI SITANGGANG', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003464', 'name' => 'ADRIAN RAMADHAN', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000794', 'name' => 'ALBERT FLINDERICO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000580', 'name' => 'ALLOYSIUS CHRISTIAN MARVELY', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005027', 'name' => 'ANGELA CLARISSA VANIA PRATISTA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025002894', 'name' => 'ANGGER RIZKY WIDIYANTOKO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025006035', 'name' => 'ANTONIO FRANCISCO MIGUEL VONG EGIDIO AMARAL', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003355', 'name' => 'BENEDICTUS ADRIAN WISNU BROTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000904', 'name' => 'BENEDICTUS CHRISTIAN SETIAJI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000803', 'name' => 'BERNARD NATHANIEL', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004374', 'name' => 'CHEVCHENCO FRANZI RODRIGO DE MENDONÇA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000873', 'name' => 'CHRISTIAN NATHANIEL', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005317', 'name' => 'DEWI FITRIYANI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12023004133', 'name' => 'DIAN VIOLIN KAPISA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004373', 'name' => 'FRANCISCO JANUÁRIO CASIMIRO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005363', 'name' => 'FRANSISKUS APRILIO BAYU PASKALIS', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004913', 'name' => 'FRANSISKUS CONAVARIO ALFIANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000800', 'name' => 'FRISCA CHRISTELLYA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003412', 'name' => 'GABRIELLA STEFANI ALWI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025002726', 'name' => 'GHREGHORHIOES DHAVID SETYADHIRJHA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003949', 'name' => 'GRATIANUS GANESHA GEMILANG PRASAJA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004622', 'name' => 'HOLLY WULAN SHIREEN GONI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025006896', 'name' => 'JEVON OZORA TJANGGULUNG SUSANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000078', 'name' => 'JOSELINE JANNETH OYA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000740', 'name' => 'KORNELIUS WILLIS PANDU PRASETYA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004041', 'name' => 'LATIFA NAYLA PRAHITO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005722', 'name' => 'LEONARD RICHARDO HETO MAREY', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003304', 'name' => 'LUNA SEKAR ARUM KUSUMA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000881', 'name' => 'MELCHIOR CLEMENS PITO HILARION SUBAN', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003546', 'name' => 'NADIA CHRISTY SINAGA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005478', 'name' => 'NAUFAL SAHLAN MAULANA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004323', 'name' => 'NAYYARA ARDINE SHAFIQA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025001300', 'name' => 'PATRIANA ADALIA CHALIS', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025006091', 'name' => 'RAFAEL ANGELO TEGAR SUTANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005553', 'name' => 'RAFAEL GABRIEL SALINDEHO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004575', 'name' => 'RANGGA SATYA KUSNANDAR', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000733', 'name' => 'REYFAN AGUSTIAN', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003095', 'name' => 'ROSA VIRGINIA FILOMENA ROMEA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005029', 'name' => 'RYO COSTARICO PEREIRA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005790', 'name' => 'STANISLAUS CARMEL FABIAN SIBARANI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004800', 'name' => 'STEFANUS WIDIRA CHRISTIANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025006965', 'name' => 'STELLA MARIS MARTINA VIANE KUKTEM', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025002704', 'name' => 'THERESIA DEVI SANTIKA SYLLA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000498', 'name' => 'VALENCIA SHAREEN LIUNARDI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003229', 'name' => 'VINCENTIO CANIORA BERKAT', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004347', 'name' => 'WILHELMUS GEORGE AGUNG PUTRANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003094', 'name' => 'YULIANI POETRI KARLINSYA BOI HENA', 'tanggal_lahir' => '2026-01-01'],
    ];

    /**
     * Teknik Informatika curriculum's mata kuliah — every course belongs to
     * this program (unlike AcademicSeeder's COURSE_CATALOG, which is spread
     * one-per-study-program across faker universities), so all of them are
     * seeded here, sequential semester_level in catalog order.
     *
     * @var array<int, array{code: string, name: string}>
     */
    private const COURSE_CATALOG = [
        ['code' => 'SFT 203', 'name' => 'Transformasi Digital'],
        ['code' => 'FTA 105', 'name' => 'Anatomi dan Fisiologi Manusia'],
        ['code' => 'SFT 303', 'name' => 'Tata Kelola Teknologi Informasi'],
        ['code' => 'SFT 101', 'name' => 'Konsep Sistem Informasi'],
        ['code' => 'SFT 207', 'name' => 'Sistem Basis Data'],
        ['code' => 'MGN 216', 'name' => 'Analisis Big Data'],
        ['code' => 'SFT 205', 'name' => 'Pemrograman Web'],
        ['code' => 'SFT 209', 'name' => 'Pemrograman Mobile'],
        ['code' => 'SFT 211', 'name' => 'Pemrograman Berorientasi Objek'],
        ['code' => 'SFT 213', 'name' => 'Pemrograman Berbasis Framework'],
        ['code' => 'SFT 215', 'name' => 'Pemrograman Berbasis Cloud'],
        ['code' => 'SFT 217', 'name' => 'Pemrograman Berbasis AI/ML'],
        ['code' => 'SFT 219', 'name' => 'Pemrograman Berbasis IoT'],
        ['code' => 'SFT 221', 'name' => 'Pemrograman Berbasis Blockchain'],
        ['code' => 'SFT 223', 'name' => 'Pemrograman Berbasis AR/VR'],
        ['code' => 'SFT 206', 'name' => 'Keamanan Jaringan'],
    ];

    public function run(): void
    {
        $university = University::query()->where('code', 'UAJ')->firstOrFail();

        $faculty = Faculty::query()->updateOrCreate(
            ['university_id' => $university->id, 'code' => 'TEK'],
            ['name' => 'Fakultas Teknik', 'is_active' => true],
        );

        $studyProgram = StudyProgram::query()->updateOrCreate(
            ['university_id' => $university->id, 'code' => 'TI1'],
            [
                'faculty_id' => $faculty->id,
                'name' => 'Teknik Informatika',
                'degree_level' => 'S1',
                'is_active' => true,
            ],
        );

        $courses = $this->seedCourses($university, $studyProgram);
        $classSections = $this->seedClassSections($university, $studyProgram, $courses);

        $students = collect(self::ROSTER)->map(function (array $entry) use ($university, $studyProgram) {
            $admissionYear = (int) substr($entry['nim'], 1, 4);

            return Student::query()->updateOrCreate(
                ['university_id' => $university->id, 'nim' => $entry['nim']],
                [
                    'study_program_id' => $studyProgram->id,
                    'name' => Str::title($entry['name']),
                    'tanggal_lahir' => $entry['tanggal_lahir'],
                    'admission_year' => $admissionYear,
                    'status' => StudentStatus::Active,
                    'enrolled_at' => "{$admissionYear}-08-01",
                ],
            );
        });

        $this->seedEnrollments($university, $classSections, $students);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Course>
     */
    private function seedCourses(University $university, StudyProgram $studyProgram): \Illuminate\Support\Collection
    {
        $curriculum = Curriculum::query()->updateOrCreate(
            ['university_id' => $university->id, 'study_program_id' => $studyProgram->id, 'name' => "Kurikulum {$studyProgram->code}"],
            ['academic_year' => '2025/2026', 'is_active' => true],
        );

        return collect(self::COURSE_CATALOG)->map(fn (array $course, int $index) => Course::query()->updateOrCreate(
            ['university_id' => $university->id, 'code' => $course['code']],
            [
                'study_program_id' => $studyProgram->id,
                'curriculum_id' => $curriculum->id,
                'name' => $course['name'],
                'credits' => 3,
                'semester_level' => $index + 1,
                'is_active' => true,
            ],
        ));
    }

    /**
     * Kelas "TI01", offered for every mata kuliah — one ClassSection row per
     * course (a class_section is 1:1 with a course in this schema, so
     * spanning all courses under the same kelas needs one row each), all in
     * the university's current academic term. Without these, the Ujian
     * ("Buat Ujian") form's Mata Kuliah dropdown (derived from
     * class_sections, see ExamsPage) has nothing to offer even though
     * Course rows exist.
     *
     * @param  \Illuminate\Support\Collection<int, Course>  $courses
     * @return \Illuminate\Support\Collection<int, ClassSection>
     */
    private function seedClassSections(University $university, StudyProgram $studyProgram, \Illuminate\Support\Collection $courses): \Illuminate\Support\Collection
    {
        $currentTerm = AcademicTerm::query()
            ->where('university_id', $university->id)
            ->where('is_current', true)
            ->firstOrFail();

        return $courses->map(fn (Course $course) => ClassSection::query()->updateOrCreate(
            ['university_id' => $university->id, 'course_id' => $course->id, 'academic_term_id' => $currentTerm->id],
            [
                'study_program_id' => $studyProgram->id,
                'class_code' => 'TI01',
                'capacity' => 50,
                'is_active' => true,
            ],
        ));
    }

    /**
     * Enrolls every roster student in every kelas (one per mata kuliah) —
     * so no matter which mata kuliah a dosen picks when building an Ujian,
     * the same full cohort of real participants is behind it.
     *
     * @param  \Illuminate\Support\Collection<int, ClassSection>  $classSections
     * @param  \Illuminate\Support\Collection<int, Student>  $students
     */
    private function seedEnrollments(University $university, \Illuminate\Support\Collection $classSections, \Illuminate\Support\Collection $students): void
    {
        foreach ($classSections as $classSection) {
            foreach ($students as $student) {
                KrsItem::query()->updateOrCreate(
                    ['student_id' => $student->id, 'class_section_id' => $classSection->id],
                    [
                        'university_id' => $university->id,
                        'academic_term_id' => $classSection->academic_term_id,
                        'status' => KrsItemStatus::Enrolled,
                    ],
                );
            }
        }
    }
}
