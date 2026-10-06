<?php

namespace Modules\ApprovalWorkflow\Contracts;

use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;

/**
 * Meresolusi approver yang bergantung pada konteks pengajuan — pemegang
 * jabatan, atasan langsung, kepala unit (ApprovalApproverType::isContextual()).
 *
 * Mesin persetujuan generik tidak tahu apa itu "jabatan" atau "atasan";
 * modul yang memiliki data itu (Modul SDM) mendaftarkan implementasinya di
 * container. Tanpa implementasi, NullContextualApproverResolver dipakai:
 * tidak ada yang berhak bertindak, bukan exception.
 */
interface ContextualApproverResolver
{
    /**
     * User id yang berhak memutuskan langkah ini untuk pengajuan tersebut.
     * Pemohon sendiri tidak pernah termasuk.
     *
     * @return list<string>
     */
    public function resolveUserIds(ApprovalWorkflowStep $step, ApprovalRequest $request): array;
}
