<?php

namespace Modules\Finance\Support;

/**
 * Aritmetika rupiah presisi tetap berbasis sen integer (BR-KEU-71) — tanpa
 * bcmath, sama dengan Modules\Admission\Support\Money. Uang dipertukarkan
 * sebagai string desimal "250000.00".
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

    public static function normalize(string|int|float|null $value): string
    {
        return self::fromCents(self::toCents($value));
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

    public static function isPositive(string|int|float|null $value): bool
    {
        return self::toCents($value) > 0;
    }

    /**
     * Persentase dari nominal, dibulatkan half-up ke kelipatan `$unitCents`
     * (pembulatan per baris, §8.3.4).
     */
    public static function percentCents(int $amountCents, float $percent, int $unitCents = 100): int
    {
        return self::roundToUnit($amountCents * $percent / 100, $unitCents);
    }

    public static function roundToUnit(float $cents, int $unitCents = 100): int
    {
        $unitCents = max(1, $unitCents);

        return (int) (floor(($cents / $unitCents) + 0.5) * $unitCents);
    }

    public static function format(string|int|float|null $value): string
    {
        return 'Rp '.number_format(self::toCents($value) / 100, 0, ',', '.');
    }
}
