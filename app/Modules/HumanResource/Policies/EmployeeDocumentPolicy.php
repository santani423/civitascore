<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\EmployeeDocument;

/**
 * Dokumen kepegawaian. Verifikasi (valid/tidak valid) memakai hr_documents.update.
 */
class EmployeeDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_documents.read');
    }

    public function view(User $user, EmployeeDocument $record): bool
    {
        return $user->hasPermissionTo('hr_documents.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_documents.create');
    }

    public function update(User $user, EmployeeDocument $record): bool
    {
        return $user->hasPermissionTo('hr_documents.update');
    }

    public function delete(User $user, EmployeeDocument $record): bool
    {
        return $user->hasPermissionTo('hr_documents.delete');
    }
}
