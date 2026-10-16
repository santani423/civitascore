<?php

namespace Modules\Finance\Payments;

/** Status hasil pemeriksaan ulang ke API penyedia (BR-KEU-41). */
final class GatewayStatus
{
    public const SUCCESS = 'success';

    public const PENDING = 'pending';

    public const FAILED = 'failed';

    public const EXPIRED = 'expired';

    public const CANCELLED = 'cancelled';

    public function __construct(
        public readonly string $status,
        public readonly ?string $amount,
        public readonly ?string $paidAt = null,
    ) {}
}
