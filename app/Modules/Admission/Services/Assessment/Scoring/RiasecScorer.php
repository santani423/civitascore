<?php

namespace Modules\Admission\Services\Assessment\Scoring;

use Modules\Admission\Exceptions\DomainRuleException;

/**
 * Scoring engine RIASEC (prompt bagian 7, Interest Scoring). Murni tanpa akses
 * database, sehingga dapat diuji dengan dataset tanpa menyentuh data pendaftar.
 *
 * Aturan `riasec-mean-v1`:
 * - Butir bertanda `reversed` dihitung sebagai (min + max − nilai).
 * - Skor mentah dimensi = jumlah nilai yang sudah disesuaikan.
 * - Rata-rata = skor mentah ÷ jumlah butir dimensi. Dasar peringkat, agar dimensi
 *   yang kebetulan memiliki lebih banyak butir tidak otomatis unggul.
 * - Persentase = posisi rata-rata dalam rentang skala (0–100). Deskriptif, bukan norma.
 * - Peringkat: rata-rata menurun; seri diurutkan dengan urutan tetap R, I, A, S, E, C.
 * - Holland Code = tiga huruf teratas; dimensi dominan = tiga teratas.
 *
 * Tidak ada bobot, norma, atau transformasi yang diterapkan. Itu menunggu pengesahan.
 */
final class RiasecScorer
{
    public const ENGINE = 'riasec-mean-v1';

    /** @var list<string> */
    public const DIMENSIONS = ['R', 'I', 'A', 'S', 'E', 'C'];

    /**
     * @param  list<array{dimension: string, reversed: bool, value: int}>  $answers  satu entri per butir yang dijawab
     * @return array{
     *     engine: string,
     *     holland_code: string,
     *     dominant: list<string>,
     *     dimensions: list<array{code: string, item_count: int, raw_score: float, mean_score: float, percent_score: float, rank: int}>
     * }
     */
    public function score(array $answers, int $scaleMin = 1, int $scaleMax = 5): array
    {
        $sums = array_fill_keys(self::DIMENSIONS, 0);
        $counts = array_fill_keys(self::DIMENSIONS, 0);

        foreach ($answers as $answer) {
            if (! in_array($answer['dimension'], self::DIMENSIONS, true)) {
                throw new DomainRuleException('Kode dimensi tidak dikenal: '.$answer['dimension'], 'INVALID_DIMENSION', 422);
            }

            if ($answer['value'] < $scaleMin || $answer['value'] > $scaleMax) {
                throw new DomainRuleException('Nilai jawaban di luar skala.', 'INVALID_VALUE', 422);
            }

            $value = $answer['reversed'] ? $scaleMin + $scaleMax - $answer['value'] : $answer['value'];
            $sums[$answer['dimension']] += $value;
            $counts[$answer['dimension']]++;
        }

        $rows = [];

        foreach (self::DIMENSIONS as $position => $code) {
            if ($counts[$code] === 0) {
                throw new DomainRuleException("Dimensi {$code} belum memiliki jawaban.", 'INCOMPLETE_INSTRUMENT', 422);
            }

            $mean = $sums[$code] / $counts[$code];
            $rows[] = [
                'code' => $code,
                'item_count' => $counts[$code],
                'raw_score' => (float) $sums[$code],
                'mean' => $mean,
                'position' => $position,
            ];
        }

        usort($rows, fn (array $a, array $b): int => ($b['mean'] <=> $a['mean']) ?: ($a['position'] <=> $b['position']));

        $dimensions = [];

        foreach ($rows as $index => $row) {
            $dimensions[] = [
                'code' => $row['code'],
                'item_count' => $row['item_count'],
                'raw_score' => $row['raw_score'],
                'mean_score' => round($row['mean'], 2),
                'percent_score' => round(($row['mean'] - $scaleMin) / ($scaleMax - $scaleMin) * 100, 2),
                'rank' => $index + 1,
            ];
        }

        $dominant = array_column(array_slice($dimensions, 0, 3), 'code');

        return [
            'engine' => self::ENGINE,
            'holland_code' => implode('', $dominant),
            'dominant' => $dominant,
            'dimensions' => $dimensions,
        ];
    }
}
