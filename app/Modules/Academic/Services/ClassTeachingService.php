<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSchedule;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Support\PortalFormatter;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;

/**
 * Dosen pengampu & jadwal mingguan kelas (PUT class-sections/{id}/teaching,
 * Tahap 2.2). Bentrok dihitung terhadap kelas aktif lain di periode yang
 * sama, tanpa tanggal efektif: tanggal term dilarang beririsan, jadi slot
 * dua periode berbeda tidak mungkin bentrok (rekonsiliasi R-02).
 *
 * - bentrok antarjadwal kelas ini sendiri dan bentrok ruangan selalu
 *   ditolak;
 * - bentrok dosen (dosen sama, jam beririsan) boleh dipaksa dengan
 *   `force` + alasan — dicatat di audit log (`forced_override`, kolom
 *   reason) dan activity log;
 * - ditolak = 409 `errors.code = SCHEDULE_CONFLICT` + `errors.conflicts[]`;
 *   setiap konflik menunjuk baris jadwal kiriman (`row`, mulai 0) supaya UI
 *   bisa menandainya per baris.
 *
 * Ruangan disimpan dalam bentuk normal (ClassSchedule::normalizeRoom()) dan
 * dibandingkan tanpa membedakan huruf besar/kecil; ruangan kosong atau
 * daring tidak pernah bentrok (ClassSchedule::roomKey()).
 */
class ClassTeachingService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string, room?: string|null}>  $rows
     * @return array{overridden_conflicts: list<array<string, mixed>>, student_conflicts: list<array<string, mixed>>}
     */
    public function update(ClassSection $classSection, ?Lecturer $lecturer, array $rows, bool $force = false, ?string $reason = null): array
    {
        $rows = array_values(array_map(fn (array $row): array => [
            'day_of_week' => (int) $row['day_of_week'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'room' => ClassSchedule::normalizeRoom($row['room'] ?? null),
        ], $rows));

        return DB::transaction(function () use ($classSection, $lecturer, $rows, $force, $reason): array {
            // Bentrok hanya dihitung di dalam satu periode, jadi mengunci
            // periode menyerialkan semua penyimpanan jadwal/dosen yang bisa
            // saling bentrok — dua permintaan paralel untuk dosen atau ruangan
            // yang sama tidak bisa sama-sama lolos cek bentrok.
            AcademicTerm::query()->whereKey($classSection->academic_term_id)->lockForUpdate()->first();

            $before = $this->snapshot($classSection->load(['lecturer', 'schedules']));
            $conflicts = $this->conflicts($classSection, $lecturer, array_map(fn (array $row) => new ClassSchedule($row), $rows));
            $blocking = array_values(array_filter($conflicts, fn (array $conflict): bool => ! ($force && $conflict['forceable'])));

            if ($blocking !== []) {
                throw new ConflictException($blocking[0]['message'], ClassSchedule::CONFLICT_CODE, ['conflicts' => $conflicts]);
            }

            $classSection->update(['lecturer_id' => $lecturer?->id]);
            $classSection->schedules()->delete();

            foreach ($rows as $row) {
                $classSection->schedules()->create(['university_id' => $classSection->university_id, ...$row]);
            }

            $classSection->refresh()->load(['course', 'lecturer', 'schedules']);
            $after = $this->snapshot($classSection);
            $this->recordHistory($classSection, $before, $after, $conflicts, $reason);

            return [
                'overridden_conflicts' => $conflicts,
                // aturan-bisnis §8.4: hanya saat jadwal berubah.
                'student_conflicts' => $before['schedules'] !== $after['schedules'] ? $this->studentConflicts($classSection) : [],
            ];
        });
    }

    /**
     * @param  list<ClassSchedule>  $schedules
     * @return list<array<string, mixed>>
     */
    private function conflicts(ClassSection $classSection, ?Lecturer $lecturer, array $schedules): array
    {
        $conflicts = [];

        foreach ($schedules as $row => $schedule) {
            foreach (array_slice($schedules, $row + 1, preserve_keys: true) as $otherRow => $other) {
                if ($schedule->overlaps($other)) {
                    $conflicts[] = $this->conflict('internal', $row, sprintf('Jadwal ke-%d dan ke-%d pada kelas ini saling bentrok (%s).', $row + 1, $otherRow + 1, PortalFormatter::scheduleText($other)), $other, otherRow: $otherRow);
                }
            }
        }

        // Kelas nonaktif/batal tidak ditawarkan, jadi tidak memakai dosen
        // maupun ruangan.
        $others = ClassSchedule::query()
            ->whereIn('day_of_week', array_unique(array_map(fn (ClassSchedule $schedule) => $schedule->day_of_week, $schedules)))
            ->whereHas('classSection', fn ($query) => $query
                ->where('academic_term_id', $classSection->academic_term_id)
                ->where('is_active', true)
                ->whereKeyNot($classSection->id))
            ->with('classSection.course')
            ->get();

        foreach ($schedules as $row => $schedule) {
            foreach ($others as $other) {
                if (! $schedule->overlaps($other)) {
                    continue;
                }

                $label = "{$other->classSection->course->name} {$other->classSection->class_code}";
                $when = PortalFormatter::scheduleText($other);

                if ($lecturer !== null && $other->classSection->lecturer_id === $lecturer->id) {
                    $conflicts[] = $this->conflict('lecturer', $row, "Jadwal bentrok dengan kelas {$label} yang juga diampu {$lecturer->name} ({$when}).", $other, $other->classSection);
                }

                if ($schedule->sameRoomAs($other)) {
                    $conflicts[] = $this->conflict('room', $row, "Ruangan {$other->room} sudah dipakai kelas {$label} ({$when}).", $other, $other->classSection);
                }
            }
        }

        return $conflicts;
    }

    /**
     * @return array<string, mixed>
     */
    private function conflict(string $type, int $row, string $message, ClassSchedule $other, ?ClassSection $otherClass = null, ?int $otherRow = null): array
    {
        return [
            'type' => $type,
            // Hanya bentrok dosen yang boleh dipaksa: dosen bisa saja
            // mengajar tim/bergantian, sedangkan satu ruangan tidak bisa
            // dipakai dua kelas sekaligus.
            'forceable' => $type === 'lecturer',
            'message' => $message,
            // Baris kiriman yang bentrok; pada bentrok antarjadwal,
            // `other_row` adalah baris pasangannya.
            'row' => $row,
            'other_row' => $otherRow,
            ...PortalFormatter::conflictSlot($other, $otherClass),
        ];
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
                'rule' => ClassSchedule::CONFLICT_CODE,
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
     * beririsan dengan kelas lain yang ia ambil di periode yang sama — satu
     * baris per mahasiswa per slot kelas lain.
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
                if ($classSection->schedules->contains(fn (ClassSchedule $schedule) => $schedule->overlaps($other))) {
                    $warnings[] = [
                        'student_id' => $item->student_id,
                        'nim' => $item->student->nim,
                        'name' => $item->student->name,
                        ...PortalFormatter::conflictSlot($other, $item->classSection),
                    ];
                }
            }
        }

        return $warnings;
    }
}
