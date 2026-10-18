<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Database\Seeders\AtmaJayaSistemInformasiStudentSeeder as SiSeeder;
use Modules\Academic\Database\Seeders\AtmaJayaStudentSeeder;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

beforeEach(function (): void {
    Storage::fake(config('pmb.storage_disk', 'local'));
});

/** @return array<string, int> */
function siSeederRowCounts(): array
{
    return [
        'faculties' => Faculty::query()->withoutGlobalScopes()->count(),
        'study_programs' => StudyProgram::query()->withoutGlobalScopes()->count(),
        'courses' => Course::query()->withoutGlobalScopes()->count(),
        'class_sections' => ClassSection::query()->withoutGlobalScopes()->count(),
        'students' => Student::query()->withoutGlobalScopes()->count(),
        'krs_items' => KrsItem::query()->withoutGlobalScopes()->count(),
        'users' => User::query()->count(),
    ];
}

test('roster contains exactly 45 unique NIMs', function (): void {
    $nims = array_column(SiSeeder::ROSTER, 'nim');

    expect(SiSeeder::ROSTER)->toHaveCount(45)
        ->and(array_unique($nims))->toHaveCount(45)
        ->and(array_filter($nims, fn (string $nim) => preg_match('/^\d{11}$/', $nim) !== 1))->toBeEmpty();
});

test('DatabaseSeeder enrolls every Sistem Informasi student in kelas SFT 303 and is idempotent', function (): void {
    $this->seed(DatabaseSeeder::class);

    $uaj = University::query()->where('code', 'UAJ')->firstOrFail();
    $program = StudyProgram::query()->withoutGlobalScopes()
        ->where('university_id', $uaj->id)->where('code', SiSeeder::STUDY_PROGRAM_CODE)->firstOrFail();
    $faculty = Faculty::query()->withoutGlobalScopes()->findOrFail($program->faculty_id);

    expect($program->name)->toBe('Sistem Informasi')
        ->and($program->degree_level)->toBe('S1')
        ->and($faculty->university_id)->toBe($uaj->id)
        ->and($faculty->code)->toBe(SiSeeder::FACULTY_CODE)
        ->and($faculty->name)->toBe('Fakultas Biosains Teknologi dan Inovasi');

    $section = ClassSection::query()->withoutGlobalScopes()
        ->where('university_id', $uaj->id)->where('class_code', SiSeeder::CLASS_CODE)->sole();
    $course = Course::query()->withoutGlobalScopes()->findOrFail($section->course_id);

    expect($course->code)->toBe('SFT 303')
        ->and($course->name)->toBe('Tata Kelola Teknologi Informasi')
        ->and($section->study_program_id)->toBe($program->id)
        ->and($section->academicTerm()->withoutGlobalScopes()->value('is_current'))->toBeTrue();

    $students = Student::query()->withoutGlobalScopes()->where('university_id', $uaj->id)
        ->whereIn('nim', array_column(SiSeeder::ROSTER, 'nim'))->get()->keyBy('nim');

    expect($students)->toHaveCount(45);

    foreach (SiSeeder::ROSTER as $entry) {
        $student = $students[$entry['nim']];

        expect($student->name)->toBe($entry['name'])
            ->and($student->study_program_id)->toBe($program->id)
            ->and($student->user_id)->not->toBeNull()
            ->and($student->email)->toBe("{$entry['nim']}@student.atmajaya.com");
    }

    $krs = KrsItem::query()->withoutGlobalScopes()->where('class_section_id', $section->id)->get();

    expect($krs)->toHaveCount(45)
        ->and($krs->pluck('student_id')->sort()->values()->all())->toBe($students->pluck('id')->sort()->values()->all())
        ->and($krs->every(fn (KrsItem $item) => $item->status === KrsItemStatus::Enrolled
            && $item->academic_term_id === $section->academic_term_id))->toBeTrue();

    // Kelas TI01 (roster Teknik Informatika) tetap utuh: satu kelas per mata
    // kuliah, dan SFT 303 kini punya dua kelas di semester yang sama.
    expect(ClassSection::query()->withoutGlobalScopes()->where('university_id', $uaj->id)->where('class_code', 'TI01')->count())->toBe(16)
        ->and(ClassSection::query()->withoutGlobalScopes()->where('course_id', $course->id)->pluck('class_code')->sort()->values()->all())->toBe(['SFT 303', 'TI01']);

    $before = siSeederRowCounts();
    $this->seed(DatabaseSeeder::class);

    expect(siSeederRowCounts())->toBe($before)
        ->and(ClassSection::query()->withoutGlobalScopes()->whereKey($section->id)->value('class_code'))->toBe(SiSeeder::CLASS_CODE);
});

test('re-running AtmaJayaStudentSeeder never turns kelas SFT 303 into TI01', function (): void {
    $this->seed(DatabaseSeeder::class);

    $section = ClassSection::query()->withoutGlobalScopes()->where('class_code', SiSeeder::CLASS_CODE)->sole();

    // Kelas TI01 untuk mata kuliah yang sama dihapus (mis. lewat aplikasi):
    // kelas SFT 303 kini satu-satunya kelas mata kuliah itu di semester aktif.
    ClassSection::query()->withoutGlobalScopes()
        ->where('course_id', $section->course_id)->where('class_code', 'TI01')->delete();

    (new AtmaJayaStudentSeeder)->run();

    $section->refresh();

    expect($section->class_code)->toBe(SiSeeder::CLASS_CODE)
        ->and(StudyProgram::query()->withoutGlobalScopes()->whereKey($section->study_program_id)->value('code'))->toBe(SiSeeder::STUDY_PROGRAM_CODE)
        ->and(ClassSection::query()->withoutGlobalScopes()->where('course_id', $section->course_id)->pluck('class_code')->sort()->values()->all())->toBe(['SFT 303', 'TI01'])
        ->and(KrsItem::query()->withoutGlobalScopes()->where('class_section_id', $section->id)->count())->toBe(45);
});
