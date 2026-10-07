<?php

namespace Modules\Academic\Services;

use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Models\Student;
use Modules\Academic\Support\AcademicClock;
use Modules\Academic\Support\PortalFormatter;

/**
 * Ringkasan kedudukan akademik seorang mahasiswa (semester ke-, IPK, SKS,
 * kurikulum, dosen wali) — dipakai bersama Dashboard, Profil, dan halaman
 * "Akademik Saya". Angka IP/IPK/SKS SELALU diambil dari
 * AcademicRecordService (satu-satunya sumber perhitungan), tidak dihitung
 * ulang di sini.
 */
class StudentAcademicService
{
    public function __construct(
        private readonly AcademicRecordService $records,
        private readonly AcademicClock $clock,
    ) {}

    public function currentTerm(): ?AcademicTerm
    {
        return AcademicTerm::query()->where('is_current', true)->orderByDesc('start_date')->first();
    }

    /**
     * Semester ke- mahasiswa pada sebuah periode, dihitung dari tahun
     * angkatan: angkatan 2024 di 2026/2027 Ganjil = semester 5.
     */
    public function semesterNumber(Student $student, ?AcademicTerm $term = null): ?int
    {
        $term ??= $this->currentTerm();

        if ($term === null) {
            return null;
        }

        $offset = ($term->startYear() - $student->admission_year) * 2
            + ($term->semester === AcademicSemester::Ganjil ? 1 : 2);

        return max(1, $offset);
    }

    /**
     * Kurikulum yang berlaku untuk mahasiswa — kurikulum aktif terbaru
     * milik program studinya (students belum menyimpan kurikulum sendiri).
     */
    public function curriculum(Student $student): ?Curriculum
    {
        return Curriculum::query()
            ->where('study_program_id', $student->study_program_id)
            ->where('is_active', true)
            ->orderByDesc('academic_year')
            ->first();
    }

    /** Total SKS seluruh mata kuliah aktif dalam kurikulum — dasar "SKS tersisa". */
    public function curriculumCredits(?Curriculum $curriculum): ?int
    {
        if ($curriculum === null) {
            return null;
        }

        $total = (int) $curriculum->courses()->where('is_active', true)->sum('credits');

        return $total > 0 ? $total : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Student $student): array
    {
        $student->loadMissing(['studyProgram.faculty', 'academicAdvisor']);

        $term = $this->currentTerm();
        $transcript = $this->records->transcript($student);
        $lastTerm = collect($transcript['terms'])->last();
        $curriculum = $this->curriculum($student);
        $curriculumCredits = $this->curriculumCredits($curriculum);

        $currentCredits = $term !== null
            ? $this->records->creditsTaken($student, $term->id)
            : 0;

        $enrolledCredits = $term !== null
            ? (int) $student->krsItems()
                ->where('academic_term_id', $term->id)
                ->where('status', KrsItemStatus::Enrolled)
                ->with('classSection.course')
                ->get()
                ->sum(fn ($item) => $item->classSection->course->credits)
            : 0;

        return [
            'nim' => $student->nim,
            'name' => $student->name,
            'study_program' => [
                'id' => $student->studyProgram->id,
                'name' => $student->studyProgram->name,
                'degree_level' => $student->studyProgram->degree_level,
            ],
            'faculty' => $student->studyProgram->faculty
                ? ['id' => $student->studyProgram->faculty->id, 'name' => $student->studyProgram->faculty->name]
                : null,
            'admission_year' => $student->admission_year,
            'semester' => $this->semesterNumber($student, $term),
            'status' => ['value' => $student->status->value, 'label' => $student->status->label()],
            'academic_advisor' => PortalFormatter::lecturer($student->academicAdvisor),
            'curriculum' => $curriculum
                ? ['id' => $curriculum->id, 'name' => $curriculum->name, 'academic_year' => $curriculum->academic_year, 'total_credits' => $curriculumCredits]
                : null,
            'current_term' => PortalFormatter::term($term, $term?->isKrsOpen($this->clock->now($student->university_id))),
            'ipk' => $transcript['ipk'],
            'last_ips' => $lastTerm ? ['academic_term_id' => $lastTerm['academic_term_id'], 'label' => $lastTerm['label'], 'ips' => $lastTerm['ip']] : null,
            'total_credits' => $transcript['total_sks'],
            'passed_credits' => $transcript['passed_sks'],
            'remaining_credits' => $curriculumCredits !== null ? max(0, $curriculumCredits - $transcript['passed_sks']) : null,
            'current_term_credits' => $currentCredits,
            'current_term_enrolled_credits' => $enrolledCredits,
            'max_credits' => $term !== null ? $this->records->maxSksForTerm($student, $term) : null,
            'enrolled_at' => $student->enrolled_at->toDateString(),
            'graduated_at' => $student->graduated_at?->toDateString(),
        ];
    }
}
