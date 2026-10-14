<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/** SM-05 — transaksi pembayaran. Transisi hanya maju (05 §F.4 aturan 4). */
enum TransactionStatus: string
{
    use TransitionsState;

    case Created = 'created';
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Created => [self::Pending, self::Failed, self::Success],
            self::Pending => [self::Success, self::Failed, self::Expired, self::Cancelled],
            self::Success => [self::Refunded],
            self::Failed, self::Expired, self::Cancelled, self::Refunded => [],
        };
    }
}
