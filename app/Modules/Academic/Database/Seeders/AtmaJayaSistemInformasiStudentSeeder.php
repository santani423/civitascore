<?php

namespace Modules\Academic\Database\Seeders;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

/**
 * Roster nyata mahasiswa Sistem Informasi (Fakultas Biosains Teknologi dan
 * Inovasi) Universitas Katolik Indonesia Atma Jaya (kode UAJ) peserta mata
 * kuliah Tata Kelola Teknologi Informasi, kelas "SFT 303" — nim/nama
 * disimpan persis seperti sumber (tanpa Str::title, tanpa tanggal lahir
 * karena sumber tidak menyediakannya). admission_year dibaca dari segmen
 * tahun pada NIM (mis. "12024000025" -> 2024), sama seperti
 * AtmaJayaStudentSeeder.
 *
 * Mata kuliah SFT 303 sudah dibuat AtmaJayaStudentSeeder (kode mata kuliah
 * unik per universitas), jadi di sini hanya dipakai ulang — harus
 * dijalankan setelahnya (lihat DemoUniversitiesSeeder). Kelasnya adalah
 * class_section terpisah milik prodi Sistem Informasi dengan class_code
 * "SFT 303" pada semester aktif, berdampingan dengan kelas TI01 milik
 * Teknik Informatika untuk mata kuliah yang sama. Dosen pengampu dibiarkan
 * kosong (lecturer_id nullable) karena tidak ada datanya.
 *
 * Idempotent dan tidak menimpa data: semua baris memakai firstOrCreate
 * dengan kunci unik masing-masing tabel — Faculty/StudyProgram per
 * (university_id, code), Student per (university_id, nim), KrsItem per
 * (student_id, class_section_id) — dan ClassSection per (university_id,
 * course_id, academic_term_id, class_code). Perubahan yang dibuat lewat
 * aplikasi (status cuti, KRS di-drop, ganti nama) tidak dikembalikan saat
 * seeder dijalankan ulang.
 */
class AtmaJayaSistemInformasiStudentSeeder extends Seeder
{
    public const FACULTY_CODE = 'FBTI';

    public const STUDY_PROGRAM_CODE = 'SI1';

    public const COURSE_CODE = 'SFT 303';

    public const CLASS_CODE = 'SFT 303';

    /** @var array<int, array{nim: string, name: string}> */
    public const ROSTER = [
        ['nim' => '12020002586', 'name' => 'JUAN KLOSE TAMBOTO'],
        ['nim' => '12021003048', 'name' => 'FASKALIS HALOMOAN LICHKMAN MANURUNG'],
        ['nim' => '12022003171', 'name' => 'SEPPY F. KOBAK'],
        ['nim' => '12022004120', 'name' => 'ALIN PERSILLA MSIREN'],
        ['nim' => '12023002860', 'name' => 'ARIANTO PASARIBU'],
        ['nim' => '12024000025', 'name' => 'MICHAEL DEVON EKA PUTRA'],
        ['nim' => '12024000541', 'name' => 'YANSEN DWI ABIMANYU'],
        ['nim' => '12024000551', 'name' => 'SHANDY HATANDI'],
        ['nim' => '12024000718', 'name' => 'EDWARD ANTONIO HERLAMBANG HUTAURUK'],
        ['nim' => '12024000739', 'name' => 'MARLIN AMALIA WIDODO'],
        ['nim' => '12024000963', 'name' => 'ALBERTUS CHANDRA HALIM'],
        ['nim' => '12024001095', 'name' => 'EZEKIEL MATHEO BARUS'],
        ['nim' => '12024001129', 'name' => 'IMMANUEL THEOPHILUS GANTARI TAHAMATA'],
        ['nim' => '12024001205', 'name' => 'ANNA PUSPITA WIJAYANTHI'],
        ['nim' => '12024001395', 'name' => 'MUHAMMAD FATHIR ALFARISI'],
        ['nim' => '12024001400', 'name' => 'ADRIANUS IVAN ARISETO'],
        ['nim' => '12024001564', 'name' => 'LEONARDUS RENALDY GERALDIO'],
        ['nim' => '12024001570', 'name' => 'THEODOMIRO FIDELIO TOMASOA'],
        ['nim' => '12024001706', 'name' => 'RIVALDO MARIANO EXPOSTO'],
        ['nim' => '12024001720', 'name' => 'JOSUE FREITAS DA COSTA'],
        ['nim' => '12024001729', 'name' => 'CORNELIUS DICHO DHARMAWAN'],
        ['nim' => '12024001801', 'name' => 'GABRIELLA LAVIGNE PINK'],
        ['nim' => '12024001815', 'name' => 'VICENTIUS ELANG KRISNANTO'],
        ['nim' => '12024001866', 'name' => 'BRIGID PAULINE RATU PATTY DIAZ'],
        ['nim' => '12024002034', 'name' => 'JO SUK SIN'],
        ['nim' => '12024002099', 'name' => 'ODA KARTIKA NURINGTYASANTI'],
        ['nim' => '12024002196', 'name' => 'MARIA ANGGITA SIDABUTAR'],
        ['nim' => '12024002253', 'name' => 'FRANS NICHOLAS RAFAEL'],
        ['nim' => '12024002321', 'name' => 'LINDA WATI PURBA'],
        ['nim' => '12024002328', 'name' => 'VIONA VINIVERA'],
        ['nim' => '12024002332', 'name' => 'JOEL OKTAVIANUS'],
        ['nim' => '12024002536', 'name' => 'RADEN RORO ANINDYA ANGGITOPUTRI'],
        ['nim' => '12024002567', 'name' => 'ALOYSIA CARLA PUTRI HARYADI'],
        ['nim' => '12024002645', 'name' => 'ANASTASYA GEOVANI MANURUNG'],
        ['nim' => '12024002831', 'name' => 'IMANUEL JULIVAL LUMBAN GAOL'],
        ['nim' => '12024002930', 'name' => 'PRISKA PRISCILLA JECONIA'],
        ['nim' => '12024003028', 'name' => 'TRACEY FIONNA'],
        ['nim' => '12024003120', 'name' => 'JEVALINE'],
        ['nim' => '12024003123', 'name' => 'BINTANG NEERA'],
        ['nim' => '12024003402', 'name' => 'JOSHUA SAHUSILAWANE'],
        ['nim' => '12024003443', 'name' => 'JESSICA HILLARY HUTAHAEAN'],
        ['nim' => '12024003516', 'name' => 'GEORGE BLACIANO KUNEMARA PUTRA'],
        ['nim' => '12024003625', 'name' => 'YOSEP HERMAWAN'],
        ['nim' => '12024003687', 'name' => 'RAFAEL ELANO PRASETYA'],
        ['nim' => '12024003719', 'name' => 'HESEKIEL ANDHIKA NAINGGOLAN'],
    ];

    public function run(): void
    {
        $university = University::query()->where('code', 'UAJ')->firstOrFail();

        // Semua query di bawah sudah memakai university_id eksplisit; konteks
        // tenant tetap dipasang supaya global scope TenantScoped tidak
        // menyaring ke kampus lain saat seeder dijalankan terpisah.
        $context = app(TenantContext::class);
        $previousUniversityId = $context->universityId();
        $context->setUniversityId($university->id);

        try {
            $this->seed($university);
        } finally {
            $context->setUniversityId($previousUniversityId);
        }
    }

    private function seed(University $university): void
    {
        $faculty = Faculty::query()->firstOrCreate(
            ['university_id' => $university->id, 'code' => self::FACULTY_CODE],
            ['name' => 'Fakultas Biosains Teknologi dan Inovasi', 'is_active' => true],
        );

        $studyProgram = StudyProgram::query()->firstOrCreate(
            ['university_id' => $university->id, 'code' => self::STUDY_PROGRAM_CODE],
            [
                'faculty_id' => $faculty->id,
                'name' => 'Sistem Informasi',
                'degree_level' => 'S1',
                'is_active' => true,
            ],
        );

        $classSection = $this->seedClassSection($university, $studyProgram);

        $students = collect(self::ROSTER)->map(function (array $entry) use ($university, $studyProgram) {
            $admissionYear = (int) substr($entry['nim'], 1, 4);

            $student = Student::query()->firstOrCreate(
                ['university_id' => $university->id, 'nim' => $entry['nim']],
                [
                    'study_program_id' => $studyProgram->id,
                    'name' => $entry['name'],
                    'admission_year' => $admissionYear,
                    'status' => StudentStatus::Active,
                    'enrolled_at' => "{$admissionYear}-08-01",
                ],
            );

            // Email kampus supaya akun login dari StudentUserAccountSeeder
            // ikut tertaut ke Atma Jaya — lihat AtmaJayaAccountSeeder.
            if ($student->email === null) {
                $student->update(['email' => AtmaJayaAccountSeeder::studentEmail($student->nim)]);
            }

            return $student;
        });

        $this->seedEnrollments($university, $classSection, $students);
    }

    /**
     * Kelas "SFT 303" untuk mata kuliah Tata Kelola Teknologi Informasi pada
     * semester aktif, ditawarkan oleh prodi Sistem Informasi.
     */
    private function seedClassSection(University $university, StudyProgram $studyProgram): ClassSection
    {
        $course = Course::query()
            ->where('university_id', $university->id)
            ->where('code', self::COURSE_CODE)
            ->firstOrFail();

        $currentTerm = AcademicTerm::query()
            ->where('university_id', $university->id)
            ->where('is_current', true)
            ->firstOrFail();

        return ClassSection::query()->firstOrCreate(
            [
                'university_id' => $university->id,
                'course_id' => $course->id,
                'academic_term_id' => $currentTerm->id,
                'class_code' => self::CLASS_CODE,
            ],
            [
                'study_program_id' => $studyProgram->id,
                'capacity' => 50,
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  Collection<int, Student>  $students
     */
    private function seedEnrollments(University $university, ClassSection $classSection, Collection $students): void
    {
        foreach ($students as $student) {
            KrsItem::query()->firstOrCreate(
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
