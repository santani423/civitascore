<?php

namespace Modules\Academic\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Lecturer;

/**
 * Satu-satunya tempat aturan "siapa terhubung ke kelas ini" untuk fitur
 * perkuliahan (materi, tugas, pengumpulan, nilai, absensi, ujian,
 * persetujuan KRS dosen wali):
 *
 * - Dosen: dikenali lewat LecturerIdentity (lecturers.user_id, lalu
 *   employees.user_id → lecturers.employee_id), bukan dari input klien.
 * - Mahasiswa: peserta kelas = KrsItem Enrolled miliknya ($user->student).
 * - Bagian Akademik: pemegang `classes.update` boleh mengelola kelas mana
 *   pun di universitasnya.
 *
 * Kepemilikan dosen atas nilai, absensi, dan ujian (Tahap 0.8) hanya
 * ditegakkan bila flag `academic.lecturer_ownership` menyala untuk
 * universitas aktif — supaya Bagian Akademik sempat mengisi dosen pengampu
 * kelas lama lebih dulu. Materi & tugas selalu ditegakkan (canManage).
 *
 * Didaftarkan scoped (per request) di AcademicServiceProvider.
 */
class ClassSectionAccess
{
    /**
     * Hak tulis perkuliahan milik dosen. Pemegang salah satunya (tanpa
     * classes.update) adalah pengajar — daftarnya dibatasi ke kelas yang
     * ia ampu. Pemegang hak baca saja (pimpinan, auditor) tetap melihat
     * semua kelas karena memang tidak pernah bisa menulis.
     *
     * @var list<string>
     */
    public const TEACHING_WRITE_PERMISSIONS = [
        'grades.create', 'grades.update',
        'attendance.create', 'attendance.update',
        'exams.create', 'exams.update', 'exams.delete', 'exams.publish',
        'exam_attempts.create', 'exam_attempts.update',
    ];

    public function __construct(
        private readonly LecturerIdentity $identity,
        private readonly AcademicFeature $features,
    ) {}

    public function lecturerFor(User $user): ?Lecturer
    {
        return $this->identity->lecturerFor($user);
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

    /** Kepemilikan dosen berlaku bagi user ini: flag menyala dan ia bukan Bagian Akademik. */
    public function enforcesOwnership(User $user): bool
    {
        return ! $user->hasPermissionTo('classes.update') && $this->features->lecturerOwnershipEnforced();
    }

    /**
     * Boleh menulis nilai/absensi/ujian kelas ini (permission dicek
     * terpisah oleh policy): selalu bila kepemilikan tidak ditegakkan,
     * selain itu hanya dosen pengampunya.
     */
    public function canWriteTeaching(User $user, ClassSection $classSection): bool
    {
        return ! $this->enforcesOwnership($user) || $this->teaches($user, $classSection);
    }

    /** Daftar & data per kelas user ini dibatasi ke kelas yang ia ampu. */
    public function limitsToOwnClasses(User $user): bool
    {
        if (! $this->enforcesOwnership($user)) {
            return false;
        }

        foreach (self::TEACHING_WRITE_PERMISSIONS as $permission) {
            if ($user->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /** Boleh melihat nilai/absensi/ujian kelas ini (permission dicek terpisah oleh policy). */
    public function canViewTeaching(User $user, ClassSection $classSection): bool
    {
        return ! $this->limitsToOwnClasses($user) || $this->teaches($user, $classSection);
    }

    /**
     * Mempersempit query daftar ke kelas yang diampu user bila
     * limitsToOwnClasses(). $column = kolom id kelas pada tabel itu
     * (`id` untuk class_sections); $through = relasi menuju tabel yang
     * punya kolom itu (mis. `krsItem` untuk nilai & absensi).
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    public function restrictToOwnClasses(Builder $query, User $user, string $column = 'class_section_id', ?string $through = null): void
    {
        if (! $this->limitsToOwnClasses($user)) {
            return;
        }

        $ownClassIds = ClassSection::query()
            ->select('id')
            ->where('lecturer_id', $this->lecturerFor($user)->id ?? '');

        $through === null
            ? $query->whereIn($column, $ownClassIds)
            : $query->whereHas($through, fn (Builder $related) => $related->whereIn($column, $ownClassIds));
    }
}
