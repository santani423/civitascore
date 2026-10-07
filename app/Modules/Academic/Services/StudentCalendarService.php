<?php

namespace Modules\Academic\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicCalendarEvent;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Assignment;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Support\AcademicClock;

/**
 * Kalender akademik pribadi mahasiswa dalam satu rentang tanggal — gabungan
 * dari sumber yang sudah ada, tanpa tabel agenda kedua:
 *
 * - academic_calendar_events (UTS/UAS/libur/wisuda yang diatur Bagian Akademik)
 * - academic_terms (awal/akhir semester, periode KRS)
 * - class_schedules kelas di KRS-nya (diekspansi per tanggal)
 * - exams yang dipublikasikan untuk kelasnya
 * - batas pengumpulan assignments kelasnya
 */
class StudentCalendarService
{
    /** Rentang maksimum satu permintaan — cukup untuk tampilan bulanan + minggu tepi. */
    public const MAX_RANGE_DAYS = 62;

    public function __construct(
        private readonly StudentScheduleService $schedules,
        private readonly StudentAcademicService $academics,
        private readonly AcademicClock $clock,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function events(Student $student, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $timezone = $this->clock->timezone($student->university_id);
        $from = $from->setTimezone($timezone)->startOfDay();
        $to = $to->setTimezone($timezone)->endOfDay();

        $classSectionIds = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->pluck('class_section_id');

        return collect()
            ->merge($this->calendarEvents($student, $from, $to))
            ->merge($this->termEvents($from, $to))
            ->merge($this->classMeetings($student, $from, $to))
            ->merge($this->examEvents($classSectionIds, $from, $to, $timezone))
            ->merge($this->assignmentEvents($classSectionIds, $from, $to, $timezone))
            ->sortBy('start')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function calendarEvents(Student $student, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return AcademicCalendarEvent::query()
            ->where(fn (Builder $query) => $query->whereNull('study_program_id')->orWhere('study_program_id', $student->study_program_id))
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->orderBy('start_date')
            ->get()
            ->map(fn (AcademicCalendarEvent $event): array => [
                'id' => "event:{$event->id}",
                'type' => 'academic',
                'category' => $event->category->value,
                'category_label' => $event->category->label(),
                'title' => $event->title,
                'description' => $event->description,
                'start' => $event->start_date->toDateString(),
                'end' => $event->end_date->toDateString(),
                'all_day' => true,
                'location' => null,
                'link' => null,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function termEvents(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $events = collect();

        $terms = AcademicTerm::query()
            ->where(fn (Builder $query) => $query
                ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                ->orWhereBetween('end_date', [$from->toDateString(), $to->toDateString()])
                ->orWhere(fn (Builder $inner) => $inner
                    ->whereNotNull('krs_start_date')
                    ->whereDate('krs_start_date', '<=', $to->toDateString())
                    ->whereDate('krs_end_date', '>=', $from->toDateString())))
            ->get();

        foreach ($terms as $term) {
            $label = $term->label();

            if ($term->start_date->betweenIncluded($from->startOfDay(), $to)) {
                $events->push($this->allDay("term-start:{$term->id}", 'semester_start', 'Awal Semester', "Awal Semester {$label}", $term->start_date, $term->start_date));
            }

            if ($term->end_date->betweenIncluded($from->startOfDay(), $to)) {
                $events->push($this->allDay("term-end:{$term->id}", 'semester_end', 'Akhir Semester', "Akhir Semester {$label}", $term->end_date, $term->end_date));
            }

            if ($term->krs_start_date !== null && $term->krs_end_date !== null
                && $term->krs_start_date->lessThanOrEqualTo($to) && $term->krs_end_date->greaterThanOrEqualTo($from->startOfDay())) {
                $events->push($this->allDay("krs:{$term->id}", 'krs', 'KRS', "Periode KRS {$label}", $term->krs_start_date, $term->krs_end_date, '/portal/krs'));
            }
        }

        return $events;
    }

    /**
     * Pertemuan mingguan diekspansi menjadi tanggal konkret di dalam rentang
     * dan masa semester aktif.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function classMeetings(Student $student, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $term = $this->academics->currentTerm();

        if ($term === null) {
            return collect();
        }

        $slots = $this->schedules->slots($student, $term);
        $start = $from->max($term->start_date->setTimezone($from->getTimezone())->startOfDay());
        $end = $to->min($term->end_date->setTimezone($from->getTimezone())->endOfDay());
        $events = collect();

        for ($date = $start->startOfDay(); $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            foreach ($slots->where('day_of_week', $date->dayOfWeekIso) as $slot) {
                [$startHour, $startMinute] = array_map('intval', explode(':', $slot['start_time']));
                [$endHour, $endMinute] = array_map('intval', explode(':', $slot['end_time']));

                $events->push([
                    'id' => "class:{$slot['id']}:{$date->toDateString()}",
                    'type' => 'class',
                    'category' => 'lecture',
                    'category_label' => 'Kuliah',
                    'title' => "{$slot['course_name']} ({$slot['class_code']})",
                    'description' => $slot['lecturer']['name'] ?? null,
                    'start' => $date->setTime($startHour, $startMinute)->toIso8601String(),
                    'end' => $date->setTime($endHour, $endMinute)->toIso8601String(),
                    'all_day' => false,
                    'location' => $slot['room'],
                    'link' => "/portal/mata-kuliah/{$slot['class_section_id']}",
                ]);
            }
        }

        return $events;
    }

    /**
     * @param  Collection<int, string>  $classSectionIds
     * @return Collection<int, array<string, mixed>>
     */
    private function examEvents(Collection $classSectionIds, CarbonImmutable $from, CarbonImmutable $to, string $timezone): Collection
    {
        return Exam::query()
            ->whereIn('class_section_id', $classSectionIds)
            ->where('is_published', true)
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', $to->utc())
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $from->utc()))
            ->with('classSection.course')
            ->get()
            ->map(fn (Exam $exam): array => [
                'id' => "exam:{$exam->id}",
                'type' => 'exam',
                'category' => 'exam',
                'category_label' => 'Ujian',
                'title' => $exam->title,
                'description' => $exam->classSection->course->name,
                'start' => $exam->starts_at->setTimezone($timezone)->toIso8601String(),
                'end' => ($exam->ends_at ?? $exam->starts_at->addMinutes($exam->duration_minutes))->setTimezone($timezone)->toIso8601String(),
                'all_day' => false,
                'location' => null,
                'link' => '/portal/ujian',
            ]);
    }

    /**
     * @param  Collection<int, string>  $classSectionIds
     * @return Collection<int, array<string, mixed>>
     */
    private function assignmentEvents(Collection $classSectionIds, CarbonImmutable $from, CarbonImmutable $to, string $timezone): Collection
    {
        return Assignment::query()
            ->whereIn('class_section_id', $classSectionIds)
            ->where('is_published', true)
            ->whereBetween('due_at', [$from->utc(), $to->utc()])
            ->with('classSection.course')
            ->get()
            ->map(fn (Assignment $assignment): array => [
                'id' => "assignment:{$assignment->id}",
                'type' => 'assignment',
                'category' => 'assignment_deadline',
                'category_label' => 'Batas Tugas',
                'title' => "Batas tugas: {$assignment->title}",
                'description' => $assignment->classSection->course->name,
                'start' => $assignment->due_at->setTimezone($timezone)->toIso8601String(),
                'end' => $assignment->due_at->setTimezone($timezone)->toIso8601String(),
                'all_day' => false,
                'location' => null,
                'link' => "/portal/tugas/{$assignment->id}",
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function allDay(string $id, string $category, string $categoryLabel, string $title, CarbonImmutable $start, CarbonImmutable $end, ?string $link = null): array
    {
        return [
            'id' => $id,
            'type' => 'academic',
            'category' => $category,
            'category_label' => $categoryLabel,
            'title' => $title,
            'description' => null,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'all_day' => true,
            'location' => null,
            'link' => $link,
        ];
    }
}
