<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\KrsItem;

/**
 * Otorisasi self-service mahasiswa mengerjakan ujian sendiri — terpisah dari
 * ExamAttemptPolicy (dosen/pengawas merekam atas nama KrsItem manapun).
 * Tidak dinamai `{Model}Policy` karena tidak mengikat ke satu Eloquent model
 * (ability-nya lintas Exam/KrsItem/ExamAttempt), jadi tidak ikut auto-discovery
 * Laravel — dipanggil langsung dari StudentExamController via `app()`.
 */
class ExamParticipationPolicy
{
    /** Gate kasar untuk index/show ujian & attempt milik sendiri. */
    public function viewOwn(User $user): bool
    {
        return $user->hasPermissionTo('exam_participation.read') && $user->student !== null;
    }

    public function startOwn(User $user, KrsItem $krsItem): bool
    {
        return $user->hasPermissionTo('exam_participation.create')
            && $user->student !== null
            && $user->student->id === $krsItem->student_id;
    }

    public function recordOwn(User $user, ExamAttempt $attempt): bool
    {
        return $user->hasPermissionTo('exam_participation.update')
            && $user->student !== null
            && $user->student->id === $attempt->krsItem->student_id;
    }
}
