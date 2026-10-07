<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Support\ClassSectionAccess;

class ClassSectionPolicy
{
    public function __construct(private readonly ClassSectionAccess $access) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('classes.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('classes.read');
    }

    /** Bagian Akademik mengatur jadwal & dosen pengampu kelas. */
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('classes.update');
    }

    /**
     * Portal Mahasiswa: hanya peserta kelas (KRS Enrolled). Kelas lain
     * dijawab 404 supaya keberadaannya tidak bocor.
     */
    public function viewAsStudent(User $user, ClassSection $classSection): Response
    {
        return $this->access->isEnrolled($user, $classSection->id)
            ? Response::allow()
            : Response::denyAsNotFound('Data tidak ditemukan.');
    }

    /**
     * Mengelola materi/tugas kelas: dosen pengampu kelas itu, atau Bagian
     * Akademik — sekaligus wajib memegang `$permission`.
     */
    public function manageLearning(User $user, ClassSection $classSection, string $permission): Response
    {
        return $this->access->canManage($user, $classSection, $permission)
            ? Response::allow()
            : Response::deny('Anda bukan dosen pengampu kelas ini.');
    }
}
