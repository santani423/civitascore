<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Academic\Models\Exam;
use Modules\Academic\Support\ClassSectionAccess;

class ExamAttemptPolicy
{
    private const NOT_OWNER = 'Anda bukan dosen pengampu kelas ini.';

    public function __construct(private readonly ClassSectionAccess $access) {}

    /** Status peserta & pelanggaran ujian ini. */
    public function viewAny(User $user, Exam $exam): Response
    {
        if (! $user->hasPermissionTo('exam_attempts.read')) {
            return Response::deny();
        }

        return $this->access->canViewTeaching($user, $exam->classSection)
            ? Response::allow()
            : Response::deny(self::NOT_OWNER);
    }

    /**
     * Satu ability untuk start/answer/submit — direkam oleh dosen/pengawas
     * atas nama peserta (lihat catatan identitas di ExamService). Bila
     * kepemilikan dosen ditegakkan, hanya untuk ujian kelas yang diampunya.
     */
    public function record(User $user, Exam $exam): Response
    {
        if (! $user->hasPermissionTo('exam_attempts.create') && ! $user->hasPermissionTo('exam_attempts.update')) {
            return Response::deny();
        }

        return $this->access->canWriteTeaching($user, $exam->classSection)
            ? Response::allow()
            : Response::deny(self::NOT_OWNER);
    }
}
