<?php

namespace Modules\Finance\Support;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;

/**
 * Pemisahan tugas (SoD, rancangan KEU §4.5): pengaju/pencatat tidak boleh
 * menyetujui tindakan yang sama. Super Admin pun tidak dikecualikan — SoD
 * adalah kontrol internal, bukan izin.
 */
final class Approvals
{
    /**
     * @param  array<int, string|null>  $excludedUserIds
     */
    public static function assertDistinct(User $approver, array $excludedUserIds, string $message = 'Pengaju atau pencatat tidak dapat menyetujui tindakan yang sama (pemisahan tugas).'): void
    {
        if (in_array((string) $approver->getKey(), array_filter($excludedUserIds), true)) {
            throw new ConflictException($message, 'SELF_APPROVAL_FORBIDDEN');
        }
    }
}
