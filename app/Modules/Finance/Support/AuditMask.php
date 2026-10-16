<?php

namespace Modules\Finance\Support;

use Modules\AuditLog\Services\AuditLogService;

/** Penyamaran data sensitif (rekening refund) di daftar — BR-KEU-72. */
final class AuditMask
{
    public static function mask(?string $value): ?string
    {
        return AuditLogService::maskValue($value);
    }
}
