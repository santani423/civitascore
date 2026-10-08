<?php

use Modules\Academic\Models\ClassSchedule;

/*
| Aturan bentrok jadwal (aturan-bisnis §8.2–8.3, pengujian-dan-penerimaan
| §1.1 "Deteksi bentrok"): irisan jam setengah-terbuka di hari yang sama, dan
| kunci ruangan yang dinormalkan. Tanpa tanggal efektif — bentrok hanya
| dibandingkan di dalam satu periode (rekonsiliasi R-02).
*/

function scheduleSlot(int $day, string $start, string $end, ?string $room = null): ClassSchedule
{
    return new ClassSchedule(['day_of_week' => $day, 'start_time' => $start, 'end_time' => $end, 'room' => $room]);
}

test('slots overlap only on the same day with intersecting half-open time ranges', function (array $a, array $b, bool $expected) {
    expect(scheduleSlot(...$a)->overlaps(scheduleSlot(...$b)))->toBe($expected)
        ->and(scheduleSlot(...$b)->overlaps(scheduleSlot(...$a)))->toBe($expected);
})->with([
    'identical' => [[1, '08:00', '10:00'], [1, '08:00', '10:00'], true],
    'partial' => [[1, '09:00', '11:00'], [1, '10:00', '12:00'], true],
    'contained' => [[1, '08:00', '12:00'], [1, '09:00', '10:00'], true],
    'touching at the end' => [[1, '09:00', '11:00'], [1, '11:00', '12:00'], false],
    'apart' => [[1, '08:00', '09:00'], [1, '13:00', '14:00'], false],
    'another day' => [[1, '08:00', '10:00'], [2, '08:00', '10:00'], false],
    // MySQL mengembalikan kolom TIME sebagai "HH:MM:SS".
    'mysql time format' => [[1, '08:00:00', '10:00:00'], [1, '09:59', '11:00'], true],
]);

test('rooms are compared after trimming, collapsing spaces and lower-casing', function () {
    expect(ClassSchedule::normalizeRoom('  Lab   Komputer '))->toBe('Lab Komputer')
        ->and(ClassSchedule::normalizeRoom('   '))->toBeNull()
        ->and(scheduleSlot(1, '08:00', '10:00', 'R.301 ')->sameRoomAs(scheduleSlot(1, '08:00', '10:00', 'r.301')))->toBeTrue()
        ->and(scheduleSlot(1, '08:00', '10:00', 'R.301')->sameRoomAs(scheduleSlot(1, '08:00', '10:00', 'R.302')))->toBeFalse();
});

test('empty and online rooms never occupy a physical room', function (?string $room) {
    expect(scheduleSlot(1, '08:00', '10:00', $room)->roomKey())->toBeNull()
        ->and(scheduleSlot(1, '08:00', '10:00', $room)->sameRoomAs(scheduleSlot(1, '08:00', '10:00', $room)))->toBeFalse();
})->with([null, '', '  ', 'online', ' Online ', 'DARING']);
