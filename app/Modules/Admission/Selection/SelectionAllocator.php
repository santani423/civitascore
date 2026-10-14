<?php

namespace Modules\Admission\Selection;

/**
 * Alokasi multi-pilihan BR-28 & tie-break BR-29 (docs/pmb/06 §H.4.6, §H.5).
 *
 * Input pelamar (sudah dievaluasi SelectionCalculator):
 *   ['id' => app_id, 'eligible' => bool, 'reason' => ?string, 'final' => float|null, 'rounded' => float|null,
 *    'normalized' => [code => N], 'verified_at' => ?string, 'choices' => [['choice_id','quota_id','priority'], ...]]
 * Kuota: quota_id => ['remaining' => int, 'threshold' => float, 'waitlist_capacity' => int]
 *
 * Deterministik: urutan & input sama → hasil sama (urutan akhir stabil dengan id).
 */
final class SelectionAllocator
{
    /**
     * @param  array<int, array<string, mixed>>  $applicants
     * @param  array<string, array{remaining: int, threshold: float, waitlist_capacity: int}>  $quotas
     * @param  array<int, string>  $tieBreakComponents  kode komponen urut prioritas tie-break
     * @param  array<string, int>  $manualOrder  app_id => posisi dalam kelompok seri (keputusan panitia)
     * @return array{results: array<string, array<string, mixed>>, has_unresolved_ties: bool}
     */
    public function allocate(array $applicants, array $quotas, array $tieBreakComponents, bool $useVerifiedAt = true, array $manualOrder = [], string $method = 'score_priority'): array
    {
        $eligible = array_values(array_filter($applicants, fn ($a) => $a['eligible']));

        usort($eligible, fn ($a, $b) => $this->compare($a, $b, $tieBreakComponents, $useVerifiedAt, $manualOrder));

        $remaining = array_map(fn ($q) => (int) $q['remaining'], $quotas);
        $waitlistCount = array_fill_keys(array_keys($quotas), 0);
        $results = [];
        $rank = 0;

        foreach ($eligible as $applicant) {
            $rank++;
            $choices = $applicant['choices'];
            usort($choices, fn ($x, $y) => $x['priority'] <=> $y['priority']);

            if ($method === 'first_choice_only') {
                $choices = array_slice($choices, 0, 1);
            }

            $assigned = null;

            foreach ($choices as $choice) {
                $quotaId = $choice['quota_id'];

                if (! isset($quotas[$quotaId])) {
                    continue;
                }

                if ($quotas[$quotaId]['threshold'] <= $applicant['rounded'] + 1e-9 && $remaining[$quotaId] > 0) {
                    $assigned = $choice;
                    $remaining[$quotaId]--;
                    break;
                }
            }

            $result = [
                'rank_global' => $rank,
                'recommendation' => 'fail',
                'fail_reason' => null,
                'assigned_choice_id' => null,
                'assigned_quota_id' => null,
                'waitlist_entries' => [],
                'tie_break_values' => $this->tieBreakValues($applicant, $tieBreakComponents, $useVerifiedAt, $manualOrder),
            ];

            if ($assigned !== null) {
                $result['recommendation'] = 'pass';
                $result['assigned_choice_id'] = $assigned['choice_id'];
                $result['assigned_quota_id'] = $assigned['quota_id'];
            } else {
                $meetsAnyThreshold = false;

                foreach ($choices as $choice) {
                    $quotaId = $choice['quota_id'];

                    if (! isset($quotas[$quotaId]) || $quotas[$quotaId]['threshold'] > $applicant['rounded'] + 1e-9) {
                        continue;
                    }

                    $meetsAnyThreshold = true;
                    $capacity = (int) $quotas[$quotaId]['waitlist_capacity'];

                    // waitlist_capacity 0 = tanpa batas (03 FR-CFG-07).
                    if ($capacity === 0 || $waitlistCount[$quotaId] < $capacity) {
                        $waitlistCount[$quotaId]++;
                        $result['waitlist_entries'][] = ['quota_id' => $quotaId, 'choice_id' => $choice['choice_id'], 'rank' => $waitlistCount[$quotaId]];
                    }
                }

                if ($result['waitlist_entries'] !== []) {
                    $result['recommendation'] = 'waitlist';
                } else {
                    $result['fail_reason'] = $meetsAnyThreshold ? 'quota_full_no_waitlist' : 'below_threshold';
                }
            }

            $results[$applicant['id']] = $result;
        }

        foreach ($applicants as $applicant) {
            if (! $applicant['eligible']) {
                $results[$applicant['id']] = [
                    'rank_global' => null,
                    'recommendation' => 'fail',
                    'fail_reason' => $applicant['reason'] ?? 'below_threshold',
                    'assigned_choice_id' => null,
                    'assigned_quota_id' => null,
                    'waitlist_entries' => [],
                    'tie_break_values' => null,
                ];
            }
        }

        $hasUnresolved = $this->markUnresolvedTies($eligible, $results, $tieBreakComponents, $useVerifiedAt, $manualOrder);

        return ['results' => $results, 'has_unresolved_ties' => $hasUnresolved];
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @param  array<int, string>  $components
     * @param  array<string, int>  $manualOrder
     */
    private function compare(array $a, array $b, array $components, bool $useVerifiedAt, array $manualOrder): int
    {
        $byKey = $this->compareKeys($a, $b, $components, $useVerifiedAt);

        if ($byKey !== 0) {
            return $byKey;
        }

        if (isset($manualOrder[$a['id']], $manualOrder[$b['id']])) {
            return $manualOrder[$a['id']] <=> $manualOrder[$b['id']];
        }

        return strcmp((string) $a['id'], (string) $b['id']);
    }

    /**
     * Urutan: skor akhir (dibulatkan) desc → N komponen tie-break desc →
     * verified_at lebih awal. Usia/jenis kelamin/agama/dsb. TIDAK pernah dipakai (BR-29).
     *
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @param  array<int, string>  $components
     */
    private function compareKeys(array $a, array $b, array $components, bool $useVerifiedAt): int
    {
        $cmp = ($b['rounded'] ?? -1) <=> ($a['rounded'] ?? -1);

        if ($cmp !== 0) {
            return $cmp;
        }

        foreach ($components as $code) {
            $cmp = ($b['normalized'][$code] ?? -1) <=> ($a['normalized'][$code] ?? -1);

            if ($cmp !== 0) {
                return $cmp;
            }
        }

        if ($useVerifiedAt && ! empty($a['verified_at']) && ! empty($b['verified_at'])) {
            return strcmp((string) $a['verified_at'], (string) $b['verified_at']);
        }

        return 0;
    }

    /**
     * Seri yang tetap sama setelah seluruh aturan → `tie_unresolved` bila
     * hasilnya berbeda di antara anggota kelompok (memperebutkan kursi/urutan
     * cadangan). Bila semua anggota mendapat kursi, seri tidak berpengaruh.
     *
     * @param  array<int, array<string, mixed>>  $sorted
     * @param  array<string, array<string, mixed>>  $results
     * @param  array<int, string>  $components
     * @param  array<string, int>  $manualOrder
     */
    private function markUnresolvedTies(array $sorted, array &$results, array $components, bool $useVerifiedAt, array $manualOrder): bool
    {
        $hasUnresolved = false;
        $count = count($sorted);

        for ($i = 0; $i < $count;) {
            $group = [$sorted[$i]];
            $j = $i + 1;

            while ($j < $count && $this->compareKeys($sorted[$i], $sorted[$j], $components, $useVerifiedAt) === 0) {
                $group[] = $sorted[$j];
                $j++;
            }

            if (count($group) > 1) {
                $resolved = collect($group)->every(fn ($member) => isset($manualOrder[$member['id']]));
                $outcomes = collect($group)->map(fn ($member) => $results[$member['id']]['recommendation'].'|'.$results[$member['id']]['assigned_quota_id']);
                $sameWaitlist = collect($group)->filter(fn ($m) => $results[$m['id']]['recommendation'] === 'waitlist')->count() > 1;
                $matters = $outcomes->unique()->count() > 1 || $sameWaitlist;

                if ($matters && ! $resolved) {
                    $hasUnresolved = true;

                    foreach ($group as $member) {
                        $results[$member['id']]['tie_unresolved'] = true;
                        $results[$member['id']]['tie_group'] = array_map(fn ($m) => $m['id'], $group);
                    }
                }
            }

            $i = $j;
        }

        return $hasUnresolved;
    }

    /**
     * @param  array<string, mixed>  $applicant
     * @param  array<int, string>  $components
     * @param  array<string, int>  $manualOrder
     * @return array<string, mixed>
     */
    private function tieBreakValues(array $applicant, array $components, bool $useVerifiedAt, array $manualOrder): array
    {
        $values = ['final_rounded' => $applicant['rounded']];

        foreach ($components as $code) {
            $values['component:'.$code] = $applicant['normalized'][$code] ?? null;
        }

        if ($useVerifiedAt) {
            $values['verified_at'] = $applicant['verified_at'] ?? null;
        }

        if (isset($manualOrder[$applicant['id']])) {
            $values['manual_order'] = $manualOrder[$applicant['id']];
        }

        return $values;
    }

    /**
     * Hash SHA-256 atas hasil terurut (H.4.6 langkah 4, J.3) — diverifikasi
     * ulang sebelum publikasi.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function hash(array $rows): string
    {
        usort($rows, fn ($a, $b) => strcmp((string) $a['application_id'], (string) $b['application_id']));

        $canonical = array_map(fn ($row) => [
            (string) $row['application_id'],
            $row['final_score_rounded'] !== null ? number_format((float) $row['final_score_rounded'], 2, '.', '') : null,
            (string) $row['recommendation'],
            $row['assigned_quota_id'] !== null ? (string) $row['assigned_quota_id'] : null,
            array_map(fn ($w) => [(string) $w['quota_id'], (int) $w['rank']], (array) ($row['waitlist_entries'] ?? [])),
            $row['override_recommendation'] ?? null,
            $row['override_quota_id'] ?? null,
        ], $rows);

        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
