<?php

namespace Modules\Academic\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\AttendanceSession;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Presensi mandiri mahasiswa lewat QR dosen.
 *
 * QR berisi `{id sesi}.{token}`. Token diturunkan dari `secret` sesi dan
 * nomor slot waktu (TOKEN_TTL_SECONDS), jadi berganti tanpa menulis ke basis
 * data. Server menerima slot sekarang dan slot sebelumnya, agar QR yang
 * dipindai tepat saat berganti tidak langsung ditolak.
 *
 * Hanya mahasiswa dengan KRS Enrolled di kelas itu yang bisa mencatat diri
 * sendiri, dan hanya sebagai Hadir. Kehadiran yang sudah dicatat dosen sebagai
 * status lain tidak ditimpa dari sisi mahasiswa.
 */
class AttendanceCheckInService
{
    public const TOKEN_TTL_SECONDS = 30;

    public const DEFAULT_DURATION_MINUTES = 15;

    private const TOKEN_LENGTH = 32;

    /** `{ULID sesi}.{token hex}`; ULID yang dibuat HasUlids berhuruf kecil. */
    private const PAYLOAD_PATTERN = '/^([0-9a-hjkmnp-tv-z]{26})\.([0-9a-f]{32})$/';

    /**
     * @param  array{meeting_number: int|string, meeting_date: string, duration_minutes?: int|string|null}  $data
     */
    public function open(ClassSection $classSection, array $data, User $actor): AttendanceSession
    {
        $now = CarbonImmutable::now();

        $running = AttendanceSession::query()
            ->where('class_section_id', $classSection->id)
            ->where('meeting_number', $data['meeting_number'])
            ->whereNull('closed_at')
            ->where('expires_at', '>', $now)
            ->exists();

        if ($running) {
            throw new ConflictException('Sesi presensi untuk pertemuan ini masih berjalan.');
        }

        return AttendanceSession::query()->create([
            'university_id' => $classSection->university_id,
            'class_section_id' => $classSection->id,
            'meeting_number' => $data['meeting_number'],
            'meeting_date' => $data['meeting_date'],
            'secret' => Str::random(64),
            'opened_by' => $actor->id,
            'expires_at' => $now->addMinutes((int) ($data['duration_minutes'] ?? self::DEFAULT_DURATION_MINUTES)),
        ]);
    }

    /**
     * Payload QR untuk slot waktu berjalan. Klien meminta ulang saat
     * `expires_at` tercapai.
     *
     * @return array{payload: string, expires_at: string, session_expires_at: string}
     */
    public function currentQr(AttendanceSession $session): array
    {
        $now = CarbonImmutable::now();

        if (! $session->isOpen($now)) {
            throw new ConflictException('Sesi presensi sudah ditutup.');
        }

        $slot = $this->slotAt($now);

        return [
            'payload' => $session->id.'.'.$this->token($session, $slot),
            'expires_at' => CarbonImmutable::createFromTimestamp(($slot + 1) * self::TOKEN_TTL_SECONDS)->toIso8601String(),
            'session_expires_at' => $session->expires_at->toIso8601String(),
        ];
    }

    public function close(AttendanceSession $session): AttendanceSession
    {
        if ($session->closed_at === null) {
            $session->update(['closed_at' => CarbonImmutable::now()]);
        }

        return $session;
    }

    /**
     * @return array{session: AttendanceSession, attendance: Attendance, recorded: bool}
     */
    public function checkIn(Student $student, string $payload): array
    {
        $now = CarbonImmutable::now();
        [$sessionId, $token] = $this->parse($payload);

        $session = AttendanceSession::query()->with('classSection.course')->find($sessionId);

        if ($session === null || ! $this->tokenMatches($session, $token, $now)) {
            throw ValidationException::withMessages([
                'payload' => 'QR tidak valid atau sudah kedaluwarsa. Pindai ulang QR yang ditampilkan dosen.',
            ]);
        }

        if (! $session->isOpen($now)) {
            throw new ConflictException('Sesi presensi sudah ditutup.');
        }

        $krsItem = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('class_section_id', $session->class_section_id)
            ->where('status', KrsItemStatus::Enrolled)
            ->first();

        if ($krsItem === null) {
            throw new AccessDeniedHttpException('Anda tidak terdaftar aktif di kelas ini.');
        }

        // firstOrCreate menangani perlombaan dua pindaian bersamaan: baris
        // kedua membaca baris yang dibuat pertama, lalu wasRecentlyCreated = false.
        $attendance = Attendance::query()->firstOrCreate(
            ['krs_item_id' => $krsItem->id, 'meeting_number' => $session->meeting_number],
            [
                'university_id' => $session->university_id,
                'meeting_date' => $session->meeting_date,
                'status' => AttendanceStatus::Present,
                'method' => 'qr',
            ],
        );

        if (! $attendance->wasRecentlyCreated && $attendance->status !== AttendanceStatus::Present) {
            throw new ConflictException(sprintf(
                'Kehadiran Anda untuk pertemuan ini sudah dicatat dosen sebagai %s. Hubungi dosen pengampu bila perlu dikoreksi.',
                $attendance->status->label(),
            ));
        }

        return [
            'session' => $session,
            'attendance' => $attendance,
            'recorded' => $attendance->wasRecentlyCreated,
        ];
    }

    /** @return array{0: string, 1: string} */
    private function parse(string $payload): array
    {
        if (preg_match(self::PAYLOAD_PATTERN, $payload, $matches) !== 1) {
            throw ValidationException::withMessages(['payload' => 'Format QR tidak dikenali.']);
        }

        return [$matches[1], $matches[2]];
    }

    private function tokenMatches(AttendanceSession $session, string $token, CarbonImmutable $now): bool
    {
        $slot = $this->slotAt($now);

        foreach ([$slot, $slot - 1] as $candidate) {
            if (hash_equals($this->token($session, $candidate), $token)) {
                return true;
            }
        }

        return false;
    }

    private function token(AttendanceSession $session, int $slot): string
    {
        return substr(hash_hmac('sha256', $session->id.':'.$slot, $session->secret), 0, self::TOKEN_LENGTH);
    }

    private function slotAt(CarbonImmutable $now): int
    {
        return intdiv($now->getTimestamp(), self::TOKEN_TTL_SECONDS);
    }
}
