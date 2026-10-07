<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalApproverType: string
{
    case Role = 'role';
    case User = 'user';
    /** Pemegang jabatan tertentu (approver_position_id). */
    case Position = 'position';
    /** Atasan langsung pemohon. */
    case DirectSupervisor = 'direct_supervisor';
    /** Kepala unit kerja pemohon. */
    case UnitHead = 'unit_head';

    /**
     * Approver yang hanya bisa diketahui dari konteks pengajuan (siapa
     * pemohonnya) — diresolusi lewat ContextualApproverResolver, bukan
     * langsung dari kolom step.
     */
    public function isContextual(): bool
    {
        return in_array($this, [self::Position, self::DirectSupervisor, self::UnitHead], true);
    }
}
