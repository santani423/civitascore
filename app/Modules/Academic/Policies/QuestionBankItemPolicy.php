<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class QuestionBankItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('question_bank.read');
    }

    /** Satu ability untuk create/update — sama seperti ExamPolicy::manage. */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('question_bank.create') || $user->hasPermissionTo('question_bank.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('question_bank.delete');
    }
}
