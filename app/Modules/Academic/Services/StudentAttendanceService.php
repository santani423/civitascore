<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Support\PortalFormatter;
use Modules\SystemSetting\Services\SystemSettingService;

/**
 * Rekap kehadiran mahasiswa per mata kuliah — dibaca dari tabel
 * `attendances` yang diisi dosen lewat AcademicRecordService (satu-satunya
 * mekanisme presensi di sistem; tidak ada presensi kedua).
 *
 * Persentase kehadiran = Hadir / total pertemuan tercatat × 100. Izin &
 * sakit tetap dilaporkan terpisah tetapi tidak dihitung hadir. Batas
 * minimum (default 75%) bisa diatur lewat system setting
 * `academic.min_attendance_percent`.
 */
class StudentAttendanceService
{
    public const DEFAULT_MIN_PERCENT = 75.0;

    public function __construct(
        private readonly StudentAcademicService $academics,
        private readonly SystemSettingService $settings,
    ) {}

    public function minimumPercent(): float
    {
        return (float) $this->settings->get('academic.min_attendance_percent', self::DEFAULT_MIN_PERCENT);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Student $student, ?string $academicTermId = null): array
    {
        $terms = $this->termsWithEnrollment($student);
        $term = $academicTermId !== null
            ? $terms->firstWhere('id', $academicTermId) ?? throw new ConflictException('Semester tidak ditemukan pada riwayat KRS Anda.')
            : ($this->academics->currentTerm() ?? $terms->last());

        $minimum = $this->minimumPercent();

        if ($term === null) {
            return ['term' => null, 'terms' => [], 'minimum_percent' => $minimum, 'courses' => [], 'overall' => $this->totals(collect())];
        }

        $items = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->with(['classSection.course', 'classSection.lecturer', 'attendances'])
            ->get();

        $courses = $items->map(function (KrsItem $item) use ($minimum): array {
            $totals = $this->totals($item->attendances);

            return [
                'krs_item_id' => $item->id,
                'class_section_id' => $item->class_section_id,
                'course_code' => $item->classSection->course->code,
                'course_name' => $item->classSection->course->name,
                'class_code' => $item->classSection->class_code,
                'credits' => $item->classSection->course->credits,
                'lecturer' => PortalFormatter::lecturer($item->classSection->lecturer),
                ...$totals,
                'is_below_minimum' => $totals['percentage'] !== null && $totals['percentage'] < $minimum,
            ];
        })->sortBy('course_name')->values();

        return [
            'term' => PortalFormatter::term($term),
            'terms' => $terms->map(fn (AcademicTerm $option) => PortalFormatter::term($option))->values()->all(),
            'minimum_percent' => $minimum,
            'courses' => $courses->all(),
            'overall' => $this->totals($items->flatMap(fn (KrsItem $item) => $item->attendances)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(KrsItem $item): array
    {
        $item->loadMissing(['classSection.course', 'classSection.lecturer', 'academicTerm', 'attendances']);

        return [
            'krs_item_id' => $item->id,
            'term' => PortalFormatter::term($item->academicTerm),
            'course_code' => $item->classSection->course->code,
            'course_name' => $item->classSection->course->name,
            'class_code' => $item->classSection->class_code,
            'lecturer' => PortalFormatter::lecturer($item->classSection->lecturer),
            'minimum_percent' => $this->minimumPercent(),
            ...$this->totals($item->attendances),
            'meetings' => $item->attendances
                ->sortBy('meeting_number')
                ->map(fn (Attendance $attendance): array => [
                    'meeting_number' => $attendance->meeting_number,
                    'meeting_date' => $attendance->meeting_date->toDateString(),
                    'status' => $attendance->status->value,
                    'status_label' => $attendance->status->label(),
                    'notes' => $attendance->notes,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  iterable<int, Attendance>  $attendances
     * @return array{total_meetings: int, present: int, permitted: int, sick: int, absent: int, percentage: float|null}
     */
    public function totals(iterable $attendances): array
    {
        $counts = ['present' => 0, 'permitted' => 0, 'sick' => 0, 'absent' => 0];

        foreach ($attendances as $attendance) {
            $counts[$attendance->status->value]++;
        }

        $total = array_sum($counts);

        return [
            'total_meetings' => $total,
            ...$counts,
            'percentage' => $total > 0 ? round($counts[AttendanceStatus::Present->value] / $total * 100, 1) : null,
        ];
    }

    /**
     * Semester yang pernah diikuti mahasiswa (ada KRS Enrolled), urut kronologis.
     *
     * @return EloquentCollection<int, AcademicTerm>
     */
    public function termsWithEnrollment(Student $student): EloquentCollection
    {
        return AcademicTerm::query()
            ->whereIn('id', KrsItem::query()
                ->where('student_id', $student->id)
                ->where('status', KrsItemStatus::Enrolled)
                ->select('academic_term_id'))
            ->orderBy('start_date')
            ->get();
    }
}
