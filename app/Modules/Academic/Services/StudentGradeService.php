<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Support\PortalFormatter;

/**
 * Nilai, KHS, dan transkrip milik mahasiswa — read-only. Semua angka IP,
 * IPK, dan SKS berasal dari AcademicRecordService (transcript(),
 * cumulativeUntil(), bestAttempts()), sumber yang sama dengan transkrip
 * admin dan dashboard, sehingga tidak mungkin berbeda antar halaman.
 */
class StudentGradeService
{
    public function __construct(
        private readonly AcademicRecordService $records,
        private readonly StudentAcademicService $academics,
    ) {}

    /**
     * Seluruh mata kuliah yang pernah diambil (Enrolled), dikelompokkan per
     * semester — termasuk yang belum dinilai.
     *
     * @return array<string, mixed>
     */
    public function grades(Student $student): array
    {
        $transcript = $this->records->transcript($student);
        $ipsByTerm = collect($transcript['terms'])->keyBy('academic_term_id');

        $terms = $this->enrolledItems($student)
            ->groupBy('academic_term_id')
            ->map(function (Collection $items) use ($student, $ipsByTerm): array {
                $term = $items->first()->academicTerm;

                return [
                    'term' => PortalFormatter::term($term),
                    'semester_number' => $this->academics->semesterNumber($student, $term),
                    'ips' => $ipsByTerm->get($term->id)['ip'] ?? null,
                    'graded_credits' => $ipsByTerm->get($term->id)['sks'] ?? 0,
                    'total_credits' => $items->sum(fn (KrsItem $item) => $item->classSection->course->credits),
                    'rows' => $items->map(fn (KrsItem $item) => $this->row($item))->sortBy('course_code')->values()->all(),
                ];
            })
            ->sortBy(fn (array $term) => $term['term']['start_date'])
            ->values()
            ->all();

        return [
            'terms' => $terms,
            'ipk' => $transcript['ipk'],
            'total_credits' => $transcript['total_sks'],
            'passed_credits' => $transcript['passed_sks'],
        ];
    }

    /**
     * KHS satu semester: nilai, bobot, mutu, IPS, dan IPK kumulatif sampai
     * dengan semester tersebut.
     *
     * @return array<string, mixed>
     */
    public function khs(Student $student, ?string $academicTermId = null): array
    {
        $items = $this->enrolledItems($student);
        $terms = $items->pluck('academicTerm')->unique('id')->sortBy(fn (AcademicTerm $term) => $term->start_date->format('Ymd'))->values();

        if ($terms->isEmpty()) {
            return ['terms' => [], 'term' => null, 'semester_number' => null, 'rows' => [], 'summary' => null];
        }

        if ($academicTermId !== null) {
            $term = $terms->firstWhere('id', $academicTermId)
                ?? throw new ConflictException('Semester tidak ditemukan pada riwayat studi Anda.');
        } else {
            // Default: semester terakhir yang sudah punya nilai, kalau belum
            // ada sama sekali — semester terakhir yang diikuti.
            $gradedTermIds = $items->filter(fn (KrsItem $item) => $item->grade?->letter_grade !== null)->pluck('academic_term_id');
            $term = $terms->filter(fn (AcademicTerm $option) => $gradedTermIds->contains($option->id))->last() ?? $terms->last();
        }

        $termItems = $items->where('academic_term_id', $term->id);
        $graded = $termItems->filter(fn (KrsItem $item) => $item->grade?->letter_grade !== null);
        $termCredits = (int) $graded->sum(fn (KrsItem $item) => $item->classSection->course->credits);
        $termPoints = (float) $graded->sum(fn (KrsItem $item) => $item->classSection->course->credits * $item->grade->letter_grade->weight());
        $cumulative = $this->records->cumulativeUntil($student, $term);

        return [
            'terms' => $terms->map(fn (AcademicTerm $option) => PortalFormatter::term($option))->all(),
            'term' => PortalFormatter::term($term),
            'semester_number' => $this->academics->semesterNumber($student, $term),
            'rows' => $termItems->map(fn (KrsItem $item) => $this->row($item))->sortBy('course_code')->values()->all(),
            'summary' => [
                'term_credits' => $termCredits,
                'taken_credits' => (int) $termItems->sum(fn (KrsItem $item) => $item->classSection->course->credits),
                'ips' => $termCredits > 0 ? round($termPoints / $termCredits, 2) : 0.0,
                'ipk' => $cumulative['ipk'],
                'cumulative_credits' => $cumulative['total_sks'],
                'all_graded' => $graded->count() === $termItems->count(),
            ],
        ];
    }

    /**
     * Transkrip: nilai terbaik setiap mata kuliah yang pernah ditempuh.
     *
     * @return array<string, mixed>
     */
    public function transcript(Student $student): array
    {
        $records = $this->records->gradedRecords($student);
        $attemptCounts = $records->countBy(fn (Grade $grade) => $grade->krsItem->classSection->course_id);
        $summary = $this->records->transcript($student);

        $rows = $this->records->bestAttempts($records)
            ->map(function (Grade $grade) use ($attemptCounts): array {
                $course = $grade->krsItem->classSection->course;
                $weight = $grade->letter_grade->weight();

                return [
                    'course_id' => $course->id,
                    'course_code' => $course->code,
                    'course_name' => $course->name,
                    'credits' => $course->credits,
                    'semester_level' => $course->semester_level,
                    'term_label' => $grade->krsItem->academicTerm->label(),
                    'term_start' => $grade->krsItem->academicTerm->start_date->toDateString(),
                    'score' => $grade->score,
                    'letter_grade' => $grade->letter_grade->value,
                    'weight' => $weight,
                    'quality_points' => round($course->credits * $weight, 2),
                    'is_passing' => $grade->letter_grade->isPassing(),
                    'attempts' => $attemptCounts[$course->id] ?? 1,
                ];
            })
            ->sortBy(fn (array $row) => $row['term_start'].'|'.$row['course_code'])
            ->values()
            ->all();

        return [
            'student' => $this->studentHeader($student),
            'rows' => $rows,
            'summary' => [
                'total_credits' => $summary['total_sks'],
                'passed_credits' => $summary['passed_sks'],
                'ipk' => $summary['ipk'],
                'course_count' => count($rows),
            ],
            'terms' => $summary['terms'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function studentHeader(Student $student): array
    {
        $student->loadMissing(['studyProgram.faculty', 'university']);

        return [
            'name' => $student->name,
            'nim' => $student->nim,
            'study_program' => $student->studyProgram->name,
            'degree_level' => $student->studyProgram->degree_level,
            'faculty' => $student->studyProgram->faculty?->name,
            'admission_year' => $student->admission_year,
            'status' => $student->status->label(),
            'university' => $student->university?->name,
        ];
    }

    /**
     * @return EloquentCollection<int, KrsItem>
     */
    private function enrolledItems(Student $student): EloquentCollection
    {
        return KrsItem::query()
            ->where('student_id', $student->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->with(['academicTerm', 'classSection.course', 'grade'])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(KrsItem $item): array
    {
        $course = $item->classSection->course;
        $letter = $item->grade?->letter_grade;
        $weight = $letter?->weight();

        return [
            'krs_item_id' => $item->id,
            'course_code' => $course->code,
            'course_name' => $course->name,
            'class_code' => $item->classSection->class_code,
            'credits' => $course->credits,
            'score' => $item->grade?->score,
            'letter_grade' => $letter?->value,
            'weight' => $weight,
            'quality_points' => $weight !== null ? round($course->credits * $weight, 2) : null,
            'is_passing' => $letter?->isPassing(),
            'is_graded' => $letter !== null,
            'graded_at' => $item->grade?->submitted_at?->toIso8601String(),
        ];
    }
}
