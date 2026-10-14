<?php

use Modules\Admission\Selection\ComponentDefinition;
use Modules\Admission\Selection\SelectionAllocator;
use Modules\Admission\Selection\SelectionCalculator;

/**
 * Golden test docs/pmb/06 §H.4.5 (AC-11): skema "Reguler 2027 v1".
 */
function goldenComponents(): array
{
    return [
        ComponentDefinition::fromArray(['code' => 'RAPOR', 'source' => 'report_card', 'raw_min' => 0, 'raw_max' => 100, 'weight' => 40, 'minimum_normalized' => 60, 'tie_break_priority' => 1]),
        ComponentDefinition::fromArray(['code' => 'TULIS', 'source' => 'written_exam', 'raw_min' => 0, 'raw_max' => 80, 'weight' => 40, 'minimum_normalized' => 40, 'tie_break_priority' => 2]),
        ComponentDefinition::fromArray(['code' => 'WWC', 'source' => 'interview', 'raw_min' => 1, 'raw_max' => 5, 'weight' => 20]),
    ];
}

function goldenApplicants(): array
{
    $raw = [
        'A' => [85.5, 62, 4, [['choice_id' => 'A1', 'quota_id' => 'IF', 'priority' => 1], ['choice_id' => 'A2', 'quota_id' => 'SI', 'priority' => 2]]],
        'B' => [90.0, 50, 5, [['choice_id' => 'B1', 'quota_id' => 'IF', 'priority' => 1]]],
        'C' => [78.0, 70, 3, [['choice_id' => 'C1', 'quota_id' => 'IF', 'priority' => 1], ['choice_id' => 'C2', 'quota_id' => 'SI', 'priority' => 2]]],
        'D' => [70.0, 28, 5, [['choice_id' => 'D1', 'quota_id' => 'IF', 'priority' => 1]]],
        'E' => [80.0, 60, 4, [['choice_id' => 'E1', 'quota_id' => 'SI', 'priority' => 1], ['choice_id' => 'E2', 'quota_id' => 'IF', 'priority' => 2]]],
    ];
    $calculator = new SelectionCalculator;
    $applicants = [];

    foreach ($raw as $id => [$rapor, $tulis, $wwc, $choices]) {
        $evaluation = $calculator->evaluate(goldenComponents(), [
            'RAPOR' => ['raw' => $rapor], 'TULIS' => ['raw' => $tulis], 'WWC' => ['raw' => $wwc],
        ]);
        $applicants[] = [
            'id' => $id,
            'eligible' => $evaluation['eligible'],
            'reason' => $evaluation['reason'],
            'final' => $evaluation['final'],
            'rounded' => $evaluation['rounded'],
            'normalized' => array_map(fn ($c) => $c['normalized'], $evaluation['components']),
            'verified_at' => null,
            'choices' => $choices,
        ];
    }

    return $applicants;
}

it('computes the documented final scores and drops D on the written-exam minimum', function () {
    $scores = collect(goldenApplicants())->keyBy('id');

    expect($scores['A']['rounded'])->toBe(80.2)
        ->and($scores['B']['rounded'])->toBe(81.0)
        ->and($scores['C']['rounded'])->toBe(76.2)
        ->and($scores['E']['rounded'])->toBe(77.0)
        ->and($scores['D']['eligible'])->toBeFalse()
        ->and($scores['D']['reason'])->toBe('component_minimum');
});

it('allocates by score then choice priority exactly as BR-28 documents', function () {
    $quotas = [
        'IF' => ['remaining' => 2, 'threshold' => 70.0, 'waitlist_capacity' => 0],
        'SI' => ['remaining' => 1, 'threshold' => 65.0, 'waitlist_capacity' => 0],
    ];

    $outcome = (new SelectionAllocator)->allocate(goldenApplicants(), $quotas, ['RAPOR', 'TULIS']);
    $r = $outcome['results'];

    expect($r['B']['recommendation'])->toBe('pass')->and($r['B']['assigned_quota_id'])->toBe('IF')
        ->and($r['A']['recommendation'])->toBe('pass')->and($r['A']['assigned_quota_id'])->toBe('IF')
        ->and($r['E']['recommendation'])->toBe('pass')->and($r['E']['assigned_quota_id'])->toBe('SI')
        ->and($r['C']['recommendation'])->toBe('waitlist')
        ->and($r['C']['waitlist_entries'])->toBe([
            ['quota_id' => 'IF', 'choice_id' => 'C1', 'rank' => 1],
            ['quota_id' => 'SI', 'choice_id' => 'C2', 'rank' => 1],
        ])
        ->and($r['D']['recommendation'])->toBe('fail')
        ->and($r['B']['rank_global'])->toBe(1)
        ->and($outcome['has_unresolved_ties'])->toBeFalse();
});

it('is deterministic and yields the same hash for the same input', function () {
    $quotas = ['IF' => ['remaining' => 2, 'threshold' => 70.0, 'waitlist_capacity' => 0], 'SI' => ['remaining' => 1, 'threshold' => 65.0, 'waitlist_capacity' => 0]];
    $run = function () use ($quotas) {
        $results = (new SelectionAllocator)->allocate(array_reverse(goldenApplicants()), $quotas, ['RAPOR', 'TULIS'])['results'];

        return SelectionAllocator::hash(array_map(fn ($id, $r) => [
            'application_id' => $id, 'final_score_rounded' => null, 'recommendation' => $r['recommendation'],
            'assigned_quota_id' => $r['assigned_quota_id'], 'waitlist_entries' => $r['waitlist_entries'],
        ], array_keys($results), $results));
    };

    expect($run())->toBe($run());
});

it('flags unresolved ties only when the tie decides a seat, and accepts both when seats suffice', function () {
    $tied = fn (string $id) => ['id' => $id, 'eligible' => true, 'reason' => null, 'final' => 75.0, 'rounded' => 75.0, 'normalized' => ['X' => 50.0], 'verified_at' => null, 'choices' => [['choice_id' => $id.'1', 'quota_id' => 'Q', 'priority' => 1]]];

    $contested = (new SelectionAllocator)->allocate([$tied('P'), $tied('R')], ['Q' => ['remaining' => 1, 'threshold' => 0, 'waitlist_capacity' => 0]], ['X'], false);
    $plenty = (new SelectionAllocator)->allocate([$tied('P'), $tied('R')], ['Q' => ['remaining' => 2, 'threshold' => 0, 'waitlist_capacity' => 0]], ['X'], false);
    $manual = (new SelectionAllocator)->allocate([$tied('P'), $tied('R')], ['Q' => ['remaining' => 1, 'threshold' => 0, 'waitlist_capacity' => 0]], ['X'], false, ['R' => 1, 'P' => 2]);

    expect($contested['has_unresolved_ties'])->toBeTrue()
        ->and($plenty['has_unresolved_ties'])->toBeFalse()
        ->and($plenty['results']['P']['recommendation'])->toBe('pass')
        ->and($plenty['results']['R']['recommendation'])->toBe('pass')
        ->and($manual['has_unresolved_ties'])->toBeFalse()
        ->and($manual['results']['R']['recommendation'])->toBe('pass')
        ->and($manual['results']['P']['recommendation'])->toBe('waitlist');
});

it('uses the earlier verification time as the third tie-break', function () {
    $app = fn (string $id, string $verified) => ['id' => $id, 'eligible' => true, 'reason' => null, 'final' => 70.0, 'rounded' => 70.0, 'normalized' => [], 'verified_at' => $verified, 'choices' => [['choice_id' => $id, 'quota_id' => 'Q', 'priority' => 1]]];

    $result = (new SelectionAllocator)->allocate([$app('LATE', '2027-03-02T10:00:00Z'), $app('EARLY', '2027-03-01T10:00:00Z')], ['Q' => ['remaining' => 1, 'threshold' => 0, 'waitlist_capacity' => 0]], []);

    expect($result['results']['EARLY']['recommendation'])->toBe('pass')
        ->and($result['results']['LATE']['recommendation'])->toBe('waitlist')
        ->and($result['has_unresolved_ties'])->toBeFalse();
});

it('rounds half up only at the final stage and treats a missing report score as unavailable', function () {
    expect(SelectionCalculator::roundHalfUp(80.195, 2))->toBe(80.2)
        ->and(SelectionCalculator::reportCardRaw([
            ['semester' => 1, 'subject_code' => 'MTK', 'score' => 80],
            ['semester' => 2, 'subject_code' => 'MTK', 'score' => 90],
        ], ['semesters' => [1, 2]]))->toBe(85.0)
        ->and(SelectionCalculator::reportCardRaw([['semester' => 1, 'subject_code' => 'MTK', 'score' => 80]], ['semesters' => [1, 2]]))->toBeNull()
        ->and(SelectionCalculator::achievementRaw([['level' => 'national', 'rank' => '1'], ['level' => 'province', 'rank' => '2']], []))->toBe(100.0)
        // Selisih antarpenguji 1 poin > 20% rentang 4 → perlu moderasi; 0,5 poin tidak.
        ->and(SelectionCalculator::rubricRaw([['a' => 4, 'b' => 2], ['a' => 4, 'b' => 4]], [['key' => 'a', 'weight' => 1], ['key' => 'b', 'weight' => 1]], 4))
        ->toBe(['raw' => 3.5, 'needs_moderation' => true])
        ->and(SelectionCalculator::rubricRaw([['a' => 4, 'b' => 3], ['a' => 4, 'b' => 4]], [['key' => 'a', 'weight' => 1], ['key' => 'b', 'weight' => 1]], 4))
        ->toBe(['raw' => 3.75, 'needs_moderation' => false]);
});
