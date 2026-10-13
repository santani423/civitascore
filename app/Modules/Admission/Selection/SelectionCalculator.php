<?php

namespace Modules\Admission\Selection;

/**
 * Rumus penilaian docs/pmb/06 §H.4 — murni (tanpa DB), deterministik.
 *
 *  N_i = (x_i − a_i)/(b_i − a_i) × 100                       (min-max)
 *  N   = clamp(50 + 10·(x − μ_s)/σ_s, 0, 100)                (zscore_session)
 *  S   = Σ w_i N_i / Σ w_i  (komponen numerik berbobot > 0 yang tersedia)
 *  eligible ⇔ verified ∧ komponen wajib tersedia ∧ N_i ≥ m_i ∧ semua pass_fail lulus
 *
 * Presisi antara 4 desimal; pembulatan half-up hanya di tahap akhir.
 */
final class SelectionCalculator
{
    public const ABSENT = 'absent';

    public const DISQUALIFIED = 'disqualified';

    /**
     * @param  array<int, ComponentDefinition>  $components
     * @param  array<string, array{raw?: float|int|string|null, passed?: bool|null, status?: string|null, session?: string|null}>  $scores  code => nilai
     * @param  array<string, array<string, array{mean: float, sd: float, n: int}>>  $sessionStats  code => session => statistik
     * @return array{eligible: bool, reason: ?string, final: ?float, rounded: ?float, components: array<string, array{raw: float|null, normalized: float|null, passed: bool|null}>}
     */
    public function evaluate(array $components, array $scores, int $roundingScale = 2, bool $disqualified = false, array $sessionStats = []): array
    {
        $detail = [];
        $reason = $disqualified ? 'disqualified' : null;
        $weighted = 0.0;
        $weights = 0.0;

        foreach ($components as $component) {
            $entry = $scores[$component->code] ?? [];
            $status = $entry['status'] ?? null;
            $raw = isset($entry['raw']) && $entry['raw'] !== '' && $entry['raw'] !== null ? (float) $entry['raw'] : null;

            if (! $component->isNumeric()) {
                $passed = $entry['passed'] ?? null;
                $detail[$component->code] = ['raw' => null, 'normalized' => null, 'passed' => $passed];

                if ($status === self::ABSENT || ($passed === null && $component->isMandatory)) {
                    $reason ??= 'absent';
                } elseif ($passed === false) {
                    $reason ??= 'component_minimum';
                }

                continue;
            }

            if ($raw === null || $status === self::ABSENT) {
                $detail[$component->code] = ['raw' => null, 'normalized' => null, 'passed' => null];

                if ($component->isMandatory) {
                    $reason ??= 'absent';
                }

                continue;
            }

            $normalized = $this->normalize($component, $raw, $entry['session'] ?? null, $sessionStats[$component->code] ?? []);
            $detail[$component->code] = ['raw' => round($raw, 4), 'normalized' => round($normalized, 4), 'passed' => null];

            if ($component->minimumNormalized !== null && $normalized + 1e-9 < $component->minimumNormalized) {
                $reason ??= 'component_minimum';
            }

            if ($component->weight > 0) {
                $weighted += $component->weight * $normalized;
                $weights += $component->weight;
            }
        }

        $final = $weights > 0 ? round($weighted / $weights, 4) : null;

        return [
            'eligible' => $reason === null && $final !== null,
            'reason' => $reason ?? ($final === null ? 'incomplete_scores' : null),
            'final' => $final,
            'rounded' => $final !== null ? self::roundHalfUp($final, $roundingScale) : null,
            'components' => $detail,
        ];
    }

    /**
     * @param  array<string, array{mean: float, sd: float, n: int}>  $stats
     */
    public function normalize(ComponentDefinition $component, float $raw, ?string $session = null, array $stats = []): float
    {
        if ($component->normalization === 'zscore_session' && $session !== null && isset($stats[$session]) && $stats[$session]['sd'] > 0) {
            $z = ($raw - $stats[$session]['mean']) / $stats[$session]['sd'];

            return max(0.0, min(100.0, 50 + 10 * $z));
        }

        $min = $component->rawMin ?? 0.0;
        $max = $component->rawMax ?? 100.0;

        if ($max <= $min) {
            return 0.0;
        }

        return max(0.0, min(100.0, ($raw - $min) / ($max - $min) * 100));
    }

    public static function roundHalfUp(float $value, int $scale): float
    {
        // Koreksi epsilon agar 80.195 (representasi biner 80.19499…) tetap naik.
        return round($value + ($value >= 0 ? 1e-9 : -1e-9), $scale, PHP_ROUND_HALF_UP);
    }

    /**
     * Komponen rapor (H.4.4): rata-rata seluruh (semester, mapel) terpilih;
     * ada nilai hilang → tidak tersedia (null), bukan nol.
     *
     * @param  array<int, array{semester: int, subject_code: string, score: float|string}>  $reportScores
     * @param  array{semesters?: array<int, int>, subjects?: array<int, string>|null}  $config
     */
    public static function reportCardRaw(array $reportScores, array $config): ?float
    {
        $semesters = $config['semesters'] ?? [1, 2, 3, 4, 5];
        $subjects = $config['subjects'] ?? null;
        $index = [];

        foreach ($reportScores as $row) {
            $index[(int) $row['semester']][(string) $row['subject_code']] = (float) $row['score'];
        }

        if ($subjects === null || $subjects === []) {
            // "Semua mapel" (Q-FRM-03 default): mapel yang ada di semester pertama terpilih menjadi acuan.
            $subjects = array_keys($index[$semesters[0]] ?? []);
        }

        if ($subjects === []) {
            return null;
        }

        $sum = 0.0;

        foreach ($semesters as $semester) {
            foreach ($subjects as $subject) {
                if (! isset($index[$semester][$subject])) {
                    return null;
                }

                $sum += $index[$semester][$subject];
            }
        }

        return round($sum / (count($semesters) * count($subjects)), 4);
    }

    /**
     * Komponen prestasi (H.4.4): Σ poin(level, rank) prestasi berdokumen
     * approved, maksimal `max_items` terbaik, dibatasi `cap`.
     *
     * @param  array<int, array{level: string, rank: string}>  $achievements
     * @param  array{points?: array<string, float|int>, rank_factor?: array<string, float>, max_items?: int, cap?: float|int}  $config
     */
    public static function achievementRaw(array $achievements, array $config): float
    {
        $points = $config['points'] ?? ['international' => 100, 'national' => 80, 'province' => 50, 'district' => 25, 'school' => 10];
        $factors = $config['rank_factor'] ?? ['1' => 1.0, '2' => 0.8, '3' => 0.6, 'finalist' => 0.3, 'participant' => 0.0];
        $values = [];

        foreach ($achievements as $achievement) {
            $values[] = (float) ($points[$achievement['level']] ?? 0) * (float) ($factors[(string) $achievement['rank']] ?? 0);
        }

        rsort($values);
        $values = array_slice($values, 0, (int) ($config['max_items'] ?? 3));

        return min((float) ($config['cap'] ?? 100), array_sum($values));
    }

    /**
     * Komponen rubrik (wawancara/portofolio, H.4.4): x = Σ v_k s_k / Σ v_k per
     * penguji, lalu rata-rata antarpenguji. Mengembalikan juga flag moderasi
     * bila selisih antarpenguji > max_examiner_gap (default 20% rentang).
     *
     * @param  array<int, array<string, float|int>>  $examinerScores  per penguji: kriteria => skor
     * @param  array<int, array{key: string, weight: float|int}>  $criteria
     * @return array{raw: ?float, needs_moderation: bool}
     */
    public static function rubricRaw(array $examinerScores, array $criteria, float $rangeSpan, ?float $maxGapPercent = null): array
    {
        $perExaminer = [];

        foreach ($examinerScores as $scores) {
            $sum = 0.0;
            $weights = 0.0;

            foreach ($criteria as $criterion) {
                if (! isset($scores[$criterion['key']])) {
                    continue 2;
                }

                $sum += (float) $criterion['weight'] * (float) $scores[$criterion['key']];
                $weights += (float) $criterion['weight'];
            }

            if ($weights > 0) {
                $perExaminer[] = $sum / $weights;
            }
        }

        if ($perExaminer === []) {
            return ['raw' => null, 'needs_moderation' => false];
        }

        $gap = max($perExaminer) - min($perExaminer);

        return [
            'raw' => round(array_sum($perExaminer) / count($perExaminer), 4),
            'needs_moderation' => count($perExaminer) > 1 && $gap > (($maxGapPercent ?? 20) / 100) * $rangeSpan,
        ];
    }
}
