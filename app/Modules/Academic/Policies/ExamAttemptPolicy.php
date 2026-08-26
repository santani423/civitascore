<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class ExamAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('exam_attempts.read');
    }

    /** Satu ability untuk start/answer/submit — direkam oleh dosen/pengawas atas nama peserta (lihat catatan identitas di ExamService). */
    public function record(User $user): bool
    {
        return $user->hasPermissionTo('exam_attempts.create') || $user->hasPermissionTo('exam_attempts.update');
    }
}
