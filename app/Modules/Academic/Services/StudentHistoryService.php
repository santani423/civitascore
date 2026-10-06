<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Collection;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudentStatusHistory;

/**
 * Timeline Riwayat Akademik mahasiswa: masuk, setiap semester yang
 * ditempuh (beserta SKS & IPS), perubahan status (cuti, aktif kembali,
 * dst. dari student_status_histories), dan kelulusan.
 */
class StudentHistoryService
{
    public function __construct(
        private readonly AcademicRecordService $records,
        private readonly StudentAcademicService $academics,
    ) {}

    /**
     * @return array<int, array{date: string, type: string, title: string, description: string|null}>
     */
    public function timeline(Student $student): array
    {
        $student->loadMissing(['studyProgram', 'university']);
        $events = collect();

        $events->push([
            'date' => $student->enrolled_at->toDateString(),
            'type' => 'admission',
            'title' => 'Masuk sebagai mahasiswa baru',
            'description' => trim("{$student->studyProgram->name} — Angkatan {$student->admission_year}".($student->university ? " · {$student->university->name}" : '')),
        ]);

        $ipsByTerm = collect($this->records->transcript($student)['terms'])->keyBy('academic_term_id');

        KrsItem::query()
            ->where('student_id', $student->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->with(['academicTerm', 'classSection.course'])
            ->get()
            ->groupBy('academic_term_id')
            ->each(function (Collection $items) use ($events, $student, $ipsByTerm): void {
                $term = $items->first()->academicTerm;
                $credits = $items->sum(fn (KrsItem $item) => $item->classSection->course->credits);
                $ips = $ipsByTerm->get($term->id)['ip'] ?? null;
                $semester = $this->academics->semesterNumber($student, $term);

                $events->push([
                    'date' => $term->start_date->toDateString(),
                    'type' => 'term',
                    'title' => "Semester {$semester} — {$term->label()}",
                    'description' => "{$items->count()} mata kuliah, {$credits} SKS".($ips !== null ? sprintf(', IPS %.2f', $ips) : ''),
                ]);
            });

        StudentStatusHistory::query()
            ->where('student_id', $student->id)
            ->orderBy('effective_date')
            ->get()
            ->each(fn (StudentStatusHistory $history) => $events->push([
                'date' => $history->effective_date->toDateString(),
                'type' => 'status',
                'title' => "Status menjadi {$history->to_status->label()}",
                'description' => collect([
                    $history->from_status ? "Sebelumnya {$history->from_status->label()}" : null,
                    $history->reason,
                ])->filter()->join(' · ') ?: null,
            ]));

        if ($student->graduated_at !== null) {
            $events->push([
                'date' => $student->graduated_at->toDateString(),
                'type' => 'graduation',
                'title' => 'Lulus',
                'description' => $student->studyProgram->name,
            ]);
        }

        return $events->sortBy('date')->values()->all();
    }
}
