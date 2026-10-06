<?php

namespace Modules\Academic\Support;

use App\Models\User;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Lecturer;

/**
 * Satu-satunya tempat aturan "siapa terhubung ke kelas ini" untuk fitur
 * perkuliahan (materi, tugas, pengumpulan, persetujuan KRS dosen wali):
 *
 * - Dosen: dikenali lewat rantai identitas users ← employees.user_id ←
 *   lecturers.employee_id (Modul SDM), bukan dari input klien.
 * - Mahasiswa: peserta kelas = KrsItem Enrolled miliknya ($user->student).
 * - Bagian Akademik: pemegang `classes.update` boleh mengelola kelas mana
 *   pun di universitasnya.
 *
 * Didaftarkan scoped (per request) di AcademicServiceProvider supaya
 * pencarian dosen untuk user yang sama tidak diulang di setiap cek.
 */
class ClassSectionAccess
{
    /** @var array<string, Lecturer|null> */
    private array $lecturers = [];

    public function lecturerFor(User $user): ?Lecturer
    {
        if (! array_key_exists($user->id, $this->lecturers)) {
            $this->lecturers[$user->id] = Lecturer::query()
                ->whereHas('employee', fn ($query) => $query->where('user_id', $user->id))
                ->first();
        }

        return $this->lecturers[$user->id];
    }

    public function teaches(User $user, ClassSection $classSection): bool
    {
        $lecturer = $this->lecturerFor($user);

        return $lecturer !== null && $classSection->lecturer_id === $lecturer->id;
    }

    public function isEnrolled(User $user, string $classSectionId): bool
    {
        $student = $user->student;

        return $student !== null && KrsItem::query()
            ->where('student_id', $student->id)
            ->where('class_section_id', $classSectionId)
            ->where('status', KrsItemStatus::Enrolled)
            ->exists();
    }

    /**
     * Pemegang `$permission` yang juga dosen pengampu kelas itu — atau
     * Bagian Akademik (`classes.update`) untuk kelas mana pun.
     */
    public function canManage(User $user, ClassSection $classSection, string $permission): bool
    {
        if (! $user->hasPermissionTo($permission)) {
            return false;
        }

        return $user->hasPermissionTo('classes.update') || $this->teaches($user, $classSection);
    }
}
