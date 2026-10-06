<?php

namespace Modules\Academic\Support;

use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSchedule;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Lecturer;

/**
 * Bentuk JSON bersama untuk potongan data yang muncul di banyak endpoint
 * Portal Mahasiswa (semester, kelas, jadwal, dosen) — supaya KRS, jadwal,
 * kalender, dan dashboard selalu mengirim struktur yang sama persis.
 */
final class PortalFormatter
{
    /**
     * @return array<string, mixed>|null
     */
    public static function term(?AcademicTerm $term, ?bool $isKrsOpen = null): ?array
    {
        if ($term === null) {
            return null;
        }

        return [
            'id' => $term->id,
            'academic_year' => $term->academic_year,
            'semester' => $term->semester->value,
            'label' => $term->label(),
            'start_date' => $term->start_date->toDateString(),
            'end_date' => $term->end_date->toDateString(),
            'is_current' => $term->is_current,
            'krs_start_date' => $term->krs_start_date?->toDateString(),
            'krs_end_date' => $term->krs_end_date?->toDateString(),
            'is_krs_open' => $isKrsOpen ?? $term->isKrsOpen(),
        ];
    }

    /**
     * @return array{id: string, name: string, email: string|null}|null
     */
    public static function lecturer(?Lecturer $lecturer): ?array
    {
        if ($lecturer === null) {
            return null;
        }

        return ['id' => $lecturer->id, 'name' => $lecturer->name, 'email' => $lecturer->email];
    }

    /**
     * @return array{id: string, day_of_week: int, day_label: string, start_time: string, end_time: string, room: string|null}
     */
    public static function schedule(ClassSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'day_of_week' => $schedule->day_of_week,
            'day_label' => $schedule->dayLabel(),
            'start_time' => $schedule->startLabel(),
            'end_time' => $schedule->endLabel(),
            'room' => $schedule->room,
        ];
    }

    /**
     * Kelas beserta mata kuliah, dosen pengampu, dan jadwalnya — relasi
     * course, lecturer, schedules harus sudah di-eager-load pemanggil.
     *
     * @return array<string, mixed>
     */
    public static function classSection(ClassSection $classSection): array
    {
        return [
            'id' => $classSection->id,
            'class_code' => $classSection->class_code,
            'course' => [
                'id' => $classSection->course->id,
                'code' => $classSection->course->code,
                'name' => $classSection->course->name,
                'credits' => $classSection->course->credits,
                'semester_level' => $classSection->course->semester_level,
            ],
            'lecturer' => self::lecturer($classSection->lecturer),
            'schedules' => $classSection->schedules->map(fn (ClassSchedule $schedule) => self::schedule($schedule))->values()->all(),
        ];
    }

    /** "Senin 08:00–09:40 (R.201)" — untuk pesan bentrok jadwal & PDF. */
    public static function scheduleText(ClassSchedule $schedule): string
    {
        $text = "{$schedule->dayLabel()} {$schedule->startLabel()}–{$schedule->endLabel()}";

        return $schedule->room ? "{$text} ({$schedule->room})" : $text;
    }
}
