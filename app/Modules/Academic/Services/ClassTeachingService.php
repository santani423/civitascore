<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\ClassSchedule;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Support\PortalFormatter;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;

/**
 * Dosen pengampu & jadwal mingguan kelas (PUT class-sections/{id}/teaching,
 * Tahap 2.2). Bentrok dihitung terhadap kelas lain di periode yang sama:
 *
 * - bentrok antarjadwal kelas ini sendiri dan bentrok ruangan selalu
 *   ditolak;
 * - bentrok dosen (dosen sama, jam beririsan) boleh dipaksa dengan
 *   `force` + alasan — dicatat di audit log (`forced_override`, kolom
 *   reason) dan activity log;
 * - ditolak = 409 `errors.code = SCHEDULE_CONFLICT` + `errors.conflicts[]`.
 *
 * Ruangan dinormalkan (trim, spasi tunggal) dan dibandingkan tanpa
 * membedakan huruf besar/kecil; ruangan kosong atau daring tidak pernah
 * bentrok.
 */
class ClassTeachingService
{
    public const CONFLICT_CODE = 'SCHEDULE_CONFLICT';

    /** Ruangan yang tidak menempati ruang fisik. */
    private const VIRTUAL_ROOMS = ['online', 'daring'];

    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string, room?: string|null}>  $rows
     * @return array{overridden_conflicts: list<array<string, mixed>>, student_conflicts: list<array<string, mixed>>}
     */
    public function update(ClassSection $classSection, ?Lecturer $lecturer, array $rows, bool $force = false, ?string $reason = null): array
    {
        $rows = array_map(fn (array $row): array => [
            'day_of_week' => (int) $row['day_of_week'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'room' => self::normalizeRoom($row['room'] ?? null),
        ], $rows);

        return DB::transaction(function () use ($classSection, $lecturer, $rows, $force, $reason): array {
            // Mengunci dosen menyerialkan penugasan paralel untuk dosen yang
            // sama, supaya dua permintaan tidak sama-sama lolos cek bentrok.
            ClassSection::query()->whereKey($classSection->id)->lockForUpdate()->first();

            if ($lecturer !== null) {
                Lecturer::query()->whereKey($lecturer->id)->lockForUpdate()->first();
            }

            $before = $this->snapshot($classSection->load(['lecturer', 'schedules']));
            $conflicts = $this->conflicts($classSection, $lecturer, array_map(fn (array $row) => new ClassSchedule($row), $rows));
            $blocking = array_values(array_filter($conflicts, fn (array $conflict): bool => ! ($force && $conflict['forceable'])));

            if ($blocking !== []) {
                throw new ConflictException($blocking[0]['message'], self::CONFLICT_CODE, ['conflicts' => $conflicts]);
            }

            $classSection->update(['lecturer_id' => $lecturer?->id]);
            $classSection->schedules()->delete();

            foreach ($rows as $row) {
                $classSection->schedules()->create(['university_id' => $classSection->university_id, ...$row]);
            }

            $classSection->refresh()->load(['course', 'lecturer', 'schedules']);
            $this->recordHistory($classSection, $before, $this->snapshot($classSection), $conflicts, $reason);

            return [
                'overridden_conflicts' => $conflicts,
                'student_conflicts' => $this->studentConflicts($classSection),
            ];
        });
    }

    public static function normalizeRoom(?string $room): ?string
    {
        $room = trim((string) preg_replace('/\s+/u', ' ', (string) $room));

        return $room === '' ? null : $room;
    }

    /**
     * @param  array<int, ClassSchedule>  $schedules
     * @return list<array<string, mixed>>
     */
    private function conflicts(ClassSection $classSection, ?Lecturer $lecturer, array $schedules): array
    {
        $conflicts = [];

        foreach ($schedules as $index => $schedule) {
            foreach (array_slice($schedules, $index + 1) as $other) {
                if ($schedule->overlaps($other)) {
                    $conflicts[] = $this->conflict('internal', 'Dua jadwal pada kelas ini saling bentrok.', $other);
                }
            }
        }

        $others = ClassSchedule::query()
            ->whereHas('classSection', fn ($query) => $query
                ->where('academic_term_id', $classSection->academic_term_id)
                ->whereKeyNot($classSection->id))
            ->with('classSection.course', 'classSection.lecturer')
            ->get();

        foreach ($schedules as $schedule) {
            foreach ($others as $other) {
                if (! $schedule->overlaps($other)) {
                    continue;
                }

                $label = "{$other->classSection->course->name} {$other->classSection->class_code}";
                $when = PortalFormatter::scheduleText($other);

                if ($lecturer !== null && $other->classSection->lecturer_id === $lecturer->id) {
                    $conflicts[] = $this->conflict('lecturer', "Jadwal bentrok dengan kelas {$label} yang juga diampu {$lecturer->name} ({$when}).", $other);
                }

                if ($this->sameRoom($schedule->room, $other->room)) {
                    $conflicts[] = $this->conflict('room', "Ruangan {$other->room} sudah dipakai kelas {$label} ({$when}).", $other);
                }
            }
        }

        return $conflicts;
    }

    /**
     * @return array<string, mixed>
     */
    private function conflict(string $type, string $message, ClassSchedule $other): array
    {
        $isOtherClass = $type !== 'internal';

        return [
            'type' => $type,
            // Hanya bentrok dosen yang boleh dipaksa: dosen bisa saja
            // mengajar tim/bergantian, sedangkan satu ruangan tidak bisa
            // dipakai dua kelas sekaligus.
            'forceable' => $type === 'lecturer',
            'message' => $message,
            'class_section_id' => $isOtherClass ? $other->classSection->id : null,
            'course_name' => $isOtherClass ? $other->classSection->course->name : null,
            'class_code' => $isOtherClass ? $other->classSection->class_code : null,
            'schedule' => PortalFormatter::scheduleText($other),
        ];
    }

    private function sameRoom(?string $room, ?string $other): bool
    {
        $room = mb_strtolower((string) self::normalizeRoom($room));
        $other = mb_strtolower((string) self::normalizeRoom($other));

        return $room !== '' && $room === $other && ! in_array($room, self::VIRTUAL_ROOMS, true);
    }

    /**
     * @return array{lecturer_id: string|null, lecturer_name: string|null, schedules: list<string>}
     */
    private function snapshot(ClassSection $classSection): array
    {
        return [
            'lecturer_id' => $classSection->lecturer_id,
            'lecturer_name' => $classSection->lecturer?->name,
            'schedules' => array_values($classSection->schedules->map(fn (ClassSchedule $schedule) => PortalFormatter::scheduleText($schedule))->all()),
        ];
    }

    /**
     * Audit log (bukti kepatuhan; alasan paksa di kolom reason) + activity
     * log (linimasa).
     *
     * @param  array{lecturer_id: string|null, lecturer_name: string|null, schedules: list<string>}  $before
     * @param  array{lecturer_id: string|null, lecturer_name: string|null, schedules: list<string>}  $after
     * @param  list<array<string, mixed>>  $overridden
     */
    private function recordHistory(ClassSection $classSection, array $before, array $after, array $overridden, ?string $reason): void
    {
        $changed = array_keys(array_filter(
            ['lecturer_id' => $before['lecturer_id'] !== $after['lecturer_id'], 'schedules' => $before['schedules'] !== $after['schedules']],
        ));

        if ($changed !== []) {
            $this->audit->record($classSection, AuditAction::Updated, $before, $after, $reason);
        }

        if (in_array('lecturer_id', $changed, true)) {
            $this->audit->log('academic', "Dosen pengampu kelas {$classSection->class_code} diubah menjadi ".($after['lecturer_name'] ?? '(belum ditetapkan)').'.', $classSection, [
                'event' => 'LECTURER_ASSIGNED',
                'old_lecturer_id' => $before['lecturer_id'],
                'new_lecturer_id' => $after['lecturer_id'],
            ]);
        }

        if (in_array('schedules', $changed, true)) {
            $this->audit->log('academic', "Jadwal kelas {$classSection->class_code} diubah.", $classSection, [
                'event' => 'CLASS_SCHEDULE_CHANGED',
                'old_schedules' => $before['schedules'],
                'new_schedules' => $after['schedules'],
            ]);
        }

        if ($overridden !== []) {
            $this->audit->recordAction($classSection, AuditAction::ForcedOverride, [
                'rule' => self::CONFLICT_CODE,
                'conflicts' => $overridden,
            ], $reason);
            $this->audit->log('academic', "Bentrok jadwal dosen pada kelas {$classSection->class_code} dipaksa: {$reason}", $classSection, [
                'event' => 'SCHEDULE_CONFLICT_OVERRIDDEN',
                'reason' => $reason,
                'conflicts' => $overridden,
            ]);
        }
    }

    /**
     * Peringatan (bukan penolakan): peserta kelas ini yang jadwal barunya
     * beririsan dengan kelas lain yang ia ambil di periode yang sama.
     *
     * @return list<array<string, mixed>>
     */
    private function studentConflicts(ClassSection $classSection): array
    {
        if ($classSection->schedules->isEmpty()) {
            return [];
        }

        $studentIds = KrsItem::query()
            ->where('class_section_id', $classSection->id)
            ->whereIn('status', KrsItemStatus::seatHolding())
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return [];
        }

        $otherItems = KrsItem::query()
            ->whereIn('student_id', $studentIds)
            ->where('academic_term_id', $classSection->academic_term_id)
            ->where('class_section_id', '!=', $classSection->id)
            ->whereIn('status', KrsItemStatus::seatHolding())
            ->with(['student', 'classSection.course', 'classSection.schedules'])
            ->get();

        $warnings = [];

        foreach ($otherItems as $item) {
            foreach ($item->classSection->schedules as $other) {
                foreach ($classSection->schedules as $schedule) {
                    if ($schedule->overlaps($other)) {
                        $warnings[] = [
                            'student_id' => $item->student_id,
                            'nim' => $item->student->nim,
                            'name' => $item->student->name,
                            'class_section_id' => $item->class_section_id,
                            'course_name' => $item->classSection->course->name,
                            'class_code' => $item->classSection->class_code,
                            'schedule' => PortalFormatter::scheduleText($other),
                        ];
                    }
                }
            }
        }

        return $warnings;
    }
}
