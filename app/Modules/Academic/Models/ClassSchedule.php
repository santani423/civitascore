<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\Factories\ClassScheduleFactory;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $class_section_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property string|null $room
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ClassSection $classSection
 */
class ClassSchedule extends Model implements ScopesToInstitution
{
    /** @use HasFactory<ClassScheduleFactory> */
    use HasFactory, HasUlids, TenantScoped;

    /** ISO-8601 day_of_week → nama hari. */
    public const DAY_LABELS = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];

    /**
     * `errors.code` 409 untuk ketiga jenis bentrok jadwal — mahasiswa (KRS),
     * dosen, dan ruangan (aturan-bisnis §8.3) — dengan `errors.conflicts[]`.
     */
    public const CONFLICT_CODE = 'SCHEDULE_CONFLICT';

    /** Ruangan yang tidak menempati ruang fisik, sehingga tidak pernah bentrok. */
    public const VIRTUAL_ROOMS = ['online', 'daring'];

    protected $fillable = ['university_id', 'class_section_id', 'day_of_week', 'start_time', 'end_time', 'room'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return BelongsTo<ClassSection, $this>
     */
    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function dayLabel(): string
    {
        return self::DAY_LABELS[$this->day_of_week] ?? '-';
    }

    /** "08:00" — MySQL mengembalikan kolom TIME sebagai "08:00:00", SQLite apa adanya. */
    public function startLabel(): string
    {
        return substr((string) $this->start_time, 0, 5);
    }

    public function endLabel(): string
    {
        return substr((string) $this->end_time, 0, 5);
    }

    public function startMinutes(): int
    {
        return self::toMinutes((string) $this->start_time);
    }

    public function endMinutes(): int
    {
        return self::toMinutes((string) $this->end_time);
    }

    /** Bentrok = hari sama dan rentang jam beririsan (bersentuhan di ujung tidak dihitung bentrok). */
    public function overlaps(self $other): bool
    {
        return $this->day_of_week === $other->day_of_week
            && $this->startMinutes() < $other->endMinutes()
            && $other->startMinutes() < $this->endMinutes();
    }

    /**
     * Bentrok ruangan = ruang fisik sama (lihat roomKey()); irisan jam dicek
     * terpisah dengan overlaps().
     */
    public function sameRoomAs(self $other): bool
    {
        $key = $this->roomKey();

        return $key !== null && $key === $other->roomKey();
    }

    /**
     * Kunci pembanding ruangan: bentuk normal dalam huruf kecil ("  R.301 "
     * dan "r.301" sama), atau null bila kosong/daring.
     */
    public function roomKey(): ?string
    {
        $key = mb_strtolower((string) self::normalizeRoom($this->room));

        return $key === '' || in_array($key, self::VIRTUAL_ROOMS, true) ? null : $key;
    }

    /** Bentuk simpan ruangan: trim + spasi tunggal, huruf dipertahankan; kosong → null. */
    public static function normalizeRoom(?string $room): ?string
    {
        $room = trim((string) preg_replace('/\s+/u', ' ', (string) $room));

        return $room === '' ? null : $room;
    }

    public static function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', array_pad(explode(':', $time), 2, 0));

        return $hours * 60 + $minutes;
    }

    protected static function newFactory(): ClassScheduleFactory
    {
        return ClassScheduleFactory::new();
    }
}
