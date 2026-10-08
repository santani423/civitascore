<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Exam;
use Modules\Academic\Support\ClassSectionAccess;

/**
 * Ujian terikat ke satu kelas. Bila kepemilikan dosen ditegakkan
 * (ClassSectionAccess), dosen hanya melihat & mengelola ujian kelas yang
 * diampunya; Bagian Akademik dan pemegang hak baca saja tidak dibatasi.
 */
class ExamPolicy
{
    private const NOT_OWNER = 'Anda bukan dosen pengampu kelas ini.';

    public function __construct(private readonly ClassSectionAccess $access) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('exams.read');
    }

    public function view(User $user, Exam $exam): Response
    {
        if (! $user->hasPermissionTo('exams.read')) {
            return Response::deny();
        }

        return $this->access->canViewTeaching($user, $exam->classSection)
            ? Response::allow()
            : Response::deny(self::NOT_OWNER);
    }

    /** Satu ability untuk create/update soal & konfigurasi ujian di kelas ini. */
    public function manage(User $user, ClassSection $classSection): Response
    {
        if (! $user->hasPermissionTo('exams.create') && ! $user->hasPermissionTo('exams.update')) {
            return Response::deny();
        }

        return $this->owns($user, $classSection);
    }

    public function delete(User $user, Exam $exam): Response
    {
        return $user->hasPermissionTo('exams.delete') ? $this->owns($user, $exam->classSection) : Response::deny();
    }

    public function publish(User $user, Exam $exam): Response
    {
        return $user->hasPermissionTo('exams.publish') ? $this->owns($user, $exam->classSection) : Response::deny();
    }

    private function owns(User $user, ClassSection $classSection): Response
    {
        return $this->access->canWriteTeaching($user, $classSection) ? Response::allow() : Response::deny(self::NOT_OWNER);
    }
}
