<?php

namespace Modules\Admission\Support;

/**
 * Aritmetika rupiah presisi tetap (2 desimal) berbasis sen integer — tidak
 * bergantung ekstensi bcmath (lingkungan shared hosting). Uang selalu
 * dipertukarkan sebagai string desimal "250000.00" (11 §A.1).
 */
final class Money
{
    public static function toCents(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $value = is_string($value) ? trim($value) : number_format((float) $value, 2, '.', '');
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-+');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);
        $cents = ((int) $whole) * 100 + (int) $fraction;

        return $negative ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $negative = $cents < 0;
        $cents = abs($cents);

        return ($negative ? '-' : '').intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function add(string|int|float|null ...$values): string
    {
        return self::fromCents(array_sum(array_map(self::toCents(...), $values)));
    }

    public static function sub(string|int|float|null $a, string|int|float|null $b): string
    {
        return self::fromCents(self::toCents($a) - self::toCents($b));
    }

    public static function compare(string|int|float|null $a, string|int|float|null $b): int
    {
        return self::toCents($a) <=> self::toCents($b);
    }

    public static function isZero(string|int|float|null $value): bool
    {
        return self::toCents($value) === 0;
    }

    /** Persentase (0–100, boleh desimal) dari nominal, dibulatkan ke sen terdekat. */
    public static function percent(string|int|float|null $amount, float $percent): string
    {
        return self::fromCents((int) round(self::toCents($amount) * $percent / 100));
    }

    public static function max(string $a, string $b): string
    {
        return self::compare($a, $b) >= 0 ? $a : $b;
    }

    public static function min(string $a, string $b): string
    {
        return self::compare($a, $b) <= 0 ? $a : $b;
    }

    public static function format(string|int|float|null $value): string
    {
        return 'Rp '.number_format(self::toCents($value) / 100, 0, ',', '.');
    }
}
