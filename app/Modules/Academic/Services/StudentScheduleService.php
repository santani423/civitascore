<?php

namespace Modules\Academic\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSchedule;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Support\AcademicClock;
use Modules\Academic\Support\PortalFormatter;

/**
 * Jadwal kuliah mahasiswa = jadwal mingguan (class_schedules) dari kelas
 * yang ada di KRS-nya semester aktif — Enrolled, plus Pending yang ditandai
 * "menunggu persetujuan". Status sedang berlangsung/akan dimulai/selesai
 * dihitung di server dengan jam lokal universitas (AcademicClock).
 */
class StudentScheduleService
{
    public function __construct(
        private readonly StudentAcademicService $academics,
        private readonly AcademicClock $clock,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Student $student): array
    {
        $term = $this->academics->currentTerm();
        $now = $this->clock->now($student->university_id);

        if ($term === null) {
            return ['term' => null, 'now' => $now->toIso8601String(), 'timezone' => $now->getTimezone()->getName(), 'is_in_term' => false, 'slots' => [], 'today' => [], 'next_class' => null];
        }

        $slots = $this->slots($student, $term);
        $isInTerm = $now->startOfDay()->betweenIncluded($term->start_date->startOfDay(), $term->end_date->startOfDay());

        return [
            'term' => PortalFormatter::term($term, $term->isKrsOpen($now)),
            'now' => $now->toIso8601String(),
            'timezone' => $now->getTimezone()->getName(),
            'is_in_term' => $isInTerm,
            'slots' => $slots->values()->all(),
            'today' => $isInTerm ? $this->today($slots, $now) : [],
            'next_class' => $this->nextClass($slots, $term, $now),
        ];
    }

    /**
     * Satu baris per pertemuan mingguan, urut hari lalu jam mulai.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function slots(Student $student, AcademicTerm $term): Collection
    {
        return $this->items($student, $term)
            ->flatMap(fn (KrsItem $item) => $item->classSection->schedules->map(
                fn (ClassSchedule $schedule): array => $this->slot($item, $item->classSection, $schedule),
            ))
            ->sortBy(fn (array $slot) => sprintf('%d|%s', $slot['day_of_week'], $slot['start_time']))
            ->values();
    }

    /**
     * Kelas hari ini beserta statusnya.
     *
     * @param  Collection<int, array<string, mixed>>  $slots
     * @return array<int, array<string, mixed>>
     */
    public function today(Collection $slots, CarbonImmutable $now): array
    {
        $minutes = $now->hour * 60 + $now->minute;

        return $slots
            ->where('day_of_week', $now->dayOfWeekIso)
            ->map(fn (array $slot): array => [
                ...$slot,
                'date' => $now->toDateString(),
                'status' => match (true) {
                    $minutes < ClassSchedule::toMinutes($slot['start_time']) => 'upcoming',
                    $minutes < ClassSchedule::toMinutes($slot['end_time']) => 'ongoing',
                    default => 'finished',
                },
            ])
            ->values()
            ->all();
    }

    /**
     * Pertemuan berikutnya yang belum dimulai (hari ini atau hari-hari
     * berikutnya, selama masih dalam masa semester).
     *
     * @param  Collection<int, array<string, mixed>>  $slots
     * @return array<string, mixed>|null
     */
    public function nextClass(Collection $slots, AcademicTerm $term, CarbonImmutable $now): ?array
    {
        if ($slots->isEmpty()) {
            return null;
        }

        $nowMinutes = $now->hour * 60 + $now->minute;

        for ($offset = 0; $offset <= 7; $offset++) {
            $date = $now->startOfDay()->addDays($offset);

            if ($date->lessThan($term->start_date->startOfDay())) {
                continue;
            }

            if ($date->greaterThan($term->end_date->startOfDay())) {
                return null;
            }

            $candidate = $slots
                ->where('day_of_week', $date->dayOfWeekIso)
                ->first(fn (array $slot) => $offset > 0 || ClassSchedule::toMinutes($slot['start_time']) > $nowMinutes);

            if ($candidate !== null) {
                [$hour, $minute] = array_map('intval', explode(':', $candidate['start_time']));

                return [
                    ...$candidate,
                    'date' => $date->toDateString(),
                    'starts_at' => $date->setTime($hour, $minute)->toIso8601String(),
                ];
            }
        }

        return null;
    }

    /**
     * @return EloquentCollection<int, KrsItem>
     */
    private function items(Student $student, AcademicTerm $term): EloquentCollection
    {
        return KrsItem::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->whereIn('status', [KrsItemStatus::Enrolled, KrsItemStatus::Pending])
            ->with(['classSection.course', 'classSection.lecturer', 'classSection.schedules'])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function slot(KrsItem $item, ClassSection $classSection, ClassSchedule $schedule): array
    {
        return [
            ...PortalFormatter::schedule($schedule),
            'krs_item_id' => $item->id,
            'krs_status' => $item->status->value,
            'is_pending_approval' => $item->status === KrsItemStatus::Pending,
            'class_section_id' => $classSection->id,
            'class_code' => $classSection->class_code,
            'course_code' => $classSection->course->code,
            'course_name' => $classSection->course->name,
            'credits' => $classSection->course->credits,
            'lecturer' => PortalFormatter::lecturer($classSection->lecturer),
        ];
    }
}
