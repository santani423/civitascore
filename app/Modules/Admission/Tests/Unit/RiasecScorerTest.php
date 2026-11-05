<?php

use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Services\Assessment\Scoring\RiasecScorer;

/** @return array{dimension: string, reversed: bool, value: int} */
function riasecAnswer(string $dimension, int $value, bool $reversed = false): array
{
    return ['dimension' => $dimension, 'reversed' => $reversed, 'value' => $value];
}

it('membalik nilai butir terbalik sebagai (min + max − nilai)', function (): void {
    $result = (new RiasecScorer)->score([
        riasecAnswer('R', 5),
        riasecAnswer('R', 1, reversed: true),
        riasecAnswer('I', 1),
        riasecAnswer('A', 3),
        riasecAnswer('S', 3),
        riasecAnswer('E', 3),
        riasecAnswer('C', 3),
    ]);

    $realistic = collect($result['dimensions'])->firstWhere('code', 'R');

    // 5 dan (1 terbalik → 5): jumlah 10 dari 2 butir, rata-rata 5, persentase 100.
    expect($realistic)->toMatchArray([
        'item_count' => 2,
        'raw_score' => 10.0,
        'mean_score' => 5.0,
        'percent_score' => 100.0,
        'rank' => 1,
    ]);
});

it('memeringkat dengan rata-rata sehingga jumlah butir tidak mengubah urutan', function (): void {
    // R memiliki tiga butir bernilai 3 (jumlah 9); I hanya satu butir bernilai 4 (jumlah 4).
    // Dengan jumlah, R menang. Dengan rata-rata, I menang (4 > 3).
    $answers = [
        riasecAnswer('R', 3), riasecAnswer('R', 3), riasecAnswer('R', 3),
        riasecAnswer('I', 4),
        riasecAnswer('A', 2), riasecAnswer('S', 2), riasecAnswer('E', 2), riasecAnswer('C', 2),
    ];

    $result = (new RiasecScorer)->score($answers);

    expect($result['dominant'])->toBe(['I', 'R', 'A']);
    expect($result['holland_code'])->toBe('IRA');
});

it('memutus seri dengan urutan tetap R, I, A, S, E, C', function (): void {
    // Semua dimensi bernilai rata-rata 3: urutan murni menurut urutan tetap.
    $answers = array_map(fn (string $code): array => riasecAnswer($code, 3), RiasecScorer::DIMENSIONS);

    $result = (new RiasecScorer)->score($answers);

    expect(array_column($result['dimensions'], 'code'))->toBe(['R', 'I', 'A', 'S', 'E', 'C']);
    expect($result['holland_code'])->toBe('RIA');
    expect(collect($result['dimensions'])->pluck('percent_score')->unique()->all())->toBe([50.0]);
});

it('menghasilkan holland code dari tiga dimensi teratas', function (): void {
    $answers = [
        riasecAnswer('S', 5), riasecAnswer('S', 4),
        riasecAnswer('E', 4), riasecAnswer('E', 5),
        riasecAnswer('C', 2),
        riasecAnswer('A', 1),
        riasecAnswer('R', 1), riasecAnswer('I', 2),
    ];

    $result = (new RiasecScorer)->score($answers);

    expect($result['engine'])->toBe(RiasecScorer::ENGINE)
        ->and($result['holland_code'])->toBe('SEI')
        ->and($result['dominant'])->toBe(['S', 'E', 'I']);
});

it('menolak dimensi tanpa jawaban agar hasil tidak dibuat dari data parsial', function (): void {
    $answers = array_map(fn (string $code): array => riasecAnswer($code, 3), ['R', 'I', 'A', 'S', 'E']);

    expect(fn () => (new RiasecScorer)->score($answers))->toThrow(function (DomainRuleException $e): void {
        expect($e->errorCode)->toBe('INCOMPLETE_INSTRUMENT');
    });
});

it('menolak nilai di luar skala dan kode dimensi yang tidak dikenal', function (): void {
    expect(fn () => (new RiasecScorer)->score([riasecAnswer('R', 6)]))
        ->toThrow(fn (DomainRuleException $e) => $e->errorCode === 'INVALID_VALUE');

    expect(fn () => (new RiasecScorer)->score([riasecAnswer('Z', 3)]))
        ->toThrow(fn (DomainRuleException $e) => $e->errorCode === 'INVALID_DIMENSION');
});
