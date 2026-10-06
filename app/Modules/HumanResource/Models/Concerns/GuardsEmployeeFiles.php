<?php

namespace Modules\HumanResource\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Implementasi Modules\FileManagement\Contracts\RestrictsFileAccess untuk
 * record SDM: berkas lampirannya (ijazah, SK, kontrak, KTP, ...) hanya
 * boleh dibuka staf yang berwenang atas data pegawai, atau pegawai pemilik
 * data itu sendiri — bukan siapa saja yang punya izin generik
 * file_uploads.read.
 *
 * @mixin Model
 */
trait GuardsEmployeeFiles
{
    public function allowsFileAccess(User $user): bool
    {
        if ($user->hasPermissionTo('hr_documents.read') || $user->hasPermissionTo('hr_employees.read')) {
            return true;
        }

        $employee = $this->employee;

        return $employee !== null && $employee->user_id !== null && $employee->user_id === $user->id;
    }
}
