<?php

namespace Modules\Admission\Support;

use Illuminate\Validation\ValidationException;

/**
 * Format NIM berbasis token (BR-36, ADM-20): {YY}, {YYYY}, {PRODI},
 * {JALUR}, {SISTEM}, {SEQ:n}. Kunci sequence = gabungan nilai SEMUA token
 * non-SEQ, sehingga NIM unik per kombinasi yang membentuknya (mencegah
 * tabrakan bila format tidak memuat kode prodi — catatan F15).
 */
final class NimFormatter
{
    public const TOKENS = ['YY', 'YYYY', 'PRODI', 'JALUR', 'SISTEM'];

    public static function validate(string $format): void
    {
        if (! preg_match('/\{SEQ:(\d)\}/', $format, $match) || (int) $match[1] < 2 || (int) $match[1] > 8) {
            throw ValidationException::withMessages(['nim_format' => 'Format wajib memuat {SEQ:n} dengan n 2–8.']);
        }

        preg_match_all('/\{([A-Z]+)(?::\d)?\}/', $format, $tokens);

        foreach ($tokens[1] as $token) {
            if ($token !== 'SEQ' && ! in_array($token, self::TOKENS, true)) {
                throw ValidationException::withMessages(['nim_format' => "Token {{$token}} tidak dikenal."]);
            }
        }

        if (preg_match('/[^A-Za-z0-9{}:\-.]/', $format) || mb_strlen($format) > 60) {
            throw ValidationException::withMessages(['nim_format' => 'Format hanya boleh berisi token dan karakter alfanumerik/titik/tanda hubung.']);
        }
    }

    /**
     * @param  array{year: int, program: string, path: string, system: string}  $values
     */
    public static function sequenceKey(string $format, array $values): string
    {
        return 'nim|'.self::render($format, $values, null);
    }

    /**
     * @param  array{year: int, program: string, path: string, system: string}  $values
     */
    public static function render(string $format, array $values, ?int $sequence): string
    {
        $replacements = [
            '{YYYY}' => (string) $values['year'],
            '{YY}' => substr((string) $values['year'], -2),
            '{PRODI}' => strtoupper($values['program']),
            '{JALUR}' => strtoupper($values['path']),
            '{SISTEM}' => strtoupper($values['system']),
        ];

        $result = strtr($format, $replacements);

        return (string) preg_replace_callback('/\{SEQ:(\d)\}/', fn ($m) => $sequence === null ? '#' : str_pad((string) $sequence, (int) $m[1], '0', STR_PAD_LEFT), $result);
    }
}
