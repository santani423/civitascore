<?php

namespace Modules\Finance\Enums;

/** Kategori kanal pembayaran (Proposal §7: Bank atau e-Commerce). */
enum ChannelCategory: string
{
    case Bank = 'bank';
    case Ecommerce = 'ecommerce';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Bank',
            self::Ecommerce => 'e-Commerce',
            self::Offline => 'Offline',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
