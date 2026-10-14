<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/**
 * SM-04 — tagihan PMB. `expired → paid` hanya lewat resolusi BR-18
 * (finance_officer memilih "terima & buka kembali").
 */
enum PmbInvoiceStatus: string
{
    use TransitionsState;

    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case RefundPending = 'refund_pending';
    case Refunded = 'refunded';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Unpaid => [self::Pending, self::Paid, self::PartiallyPaid, self::Expired, self::Cancelled],
            self::Pending => [self::Unpaid, self::Paid, self::PartiallyPaid, self::Expired, self::Cancelled],
            self::PartiallyPaid => [self::Pending, self::Paid, self::RefundPending, self::PartiallyPaid],
            self::Paid => [self::RefundPending],
            self::Expired => [self::Paid, self::PartiallyPaid],
            self::Cancelled => [],
            self::RefundPending => [self::Refunded, self::Paid, self::PartiallyPaid],
            self::Refunded => [],
        };
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::Unpaid, self::Pending, self::PartiallyPaid], true);
    }
}
