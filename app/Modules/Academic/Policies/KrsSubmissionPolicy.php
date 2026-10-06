<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Modules\Academic\Models\KrsSubmission;
use Modules\Academic\Support\ClassSectionAccess;

/**
 * Persetujuan KRS: Bagian Akademik (`krs.approve`) untuk mahasiswa mana pun
 * di universitasnya; dosen wali (`krs_advising.*`) hanya untuk mahasiswa
 * yang students.academic_advisor_id-nya menunjuk dirinya — dikenali lewat
 * identity link dosen (ClassSectionAccess::lecturerFor), bukan input klien.
 */
class KrsSubmissionPolicy
{
    public function __construct(private readonly ClassSectionAccess $access) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('krs.approve') || $user->hasPermissionTo('krs_advising.read');
    }

    public function view(User $user, KrsSubmission $submission): bool
    {
        return $user->hasPermissionTo('krs.approve')
            || ($user->hasPermissionTo('krs_advising.read') && $this->isAdvisor($user, $submission));
    }

    public function decide(User $user, KrsSubmission $submission): bool
    {
        return $user->hasPermissionTo('krs.approve')
            || ($user->hasPermissionTo('krs_advising.approve') && $this->isAdvisor($user, $submission));
    }

    private function isAdvisor(User $user, KrsSubmission $submission): bool
    {
        $lecturer = $this->access->lecturerFor($user);

        return $lecturer !== null && $submission->student->academic_advisor_id === $lecturer->id;
    }
}
