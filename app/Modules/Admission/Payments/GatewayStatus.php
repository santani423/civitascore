<?php

namespace Modules\Admission\Payments;

/** Status transaksi hasil double-check ke API penyedia (05 §F.4 aturan 3). */
final class GatewayStatus
{
    public const SUCCESS = 'success';

    public const PENDING = 'pending';

    public const FAILED = 'failed';

    public const EXPIRED = 'expired';

    public const CANCELLED = 'cancelled';

    public const REFUNDED = 'refunded';

    public function __construct(
        public readonly string $status,
        public readonly ?string $amount,
        public readonly string $currency = 'IDR',
        public readonly ?string $paidAt = null,
        public readonly ?string $raw = null,
    ) {}
}
