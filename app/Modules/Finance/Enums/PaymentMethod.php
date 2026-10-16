<?php

namespace Modules\Finance\Enums;

/** Metode pembayaran transaksi. */
enum PaymentMethod: string
{
    case Online = 'online';
    case ManualTransfer = 'manual_transfer';
    case Cash = 'cash';
    case Deposit = 'deposit';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::ManualTransfer => 'Transfer Manual',
            self::Cash => 'Tunai (Loket)',
            self::Deposit => 'Saldo Deposit',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
