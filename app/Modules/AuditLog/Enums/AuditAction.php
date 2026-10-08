<?php

namespace Modules\AuditLog\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Exported = 'exported';
    case Imported = 'imported';
    // Aksi non-CRUD yang tetap wajib terlacak (RANCANGAN-AKUN-SDM §5.22).
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Returned = 'returned';
    case Resubmitted = 'resubmitted';
    case Delegated = 'delegated';
    case Verified = 'verified';
    case Downloaded = 'downloaded';
    case ViewedSensitive = 'viewed_sensitive';
    case LinkedAccount = 'linked_account';
    // Aturan bisnis sengaja dilewati dengan alasan (mis. bentrok jadwal dosen dipaksa).
    case ForcedOverride = 'forced_override';
}
