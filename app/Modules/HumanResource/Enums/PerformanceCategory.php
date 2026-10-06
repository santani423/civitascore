<?php

namespace Modules\HumanResource\Enums;

enum PerformanceCategory: string
{
    case VeryGood = 'very_good';
    case Good = 'good';
    case Fair = 'fair';
    case NeedsImprovement = 'needs_improvement';

    public function label(): string
    {
        return match ($this) {
            self::VeryGood => 'Sangat Baik',
            self::Good => 'Baik',
            self::Fair => 'Cukup',
            self::NeedsImprovement => 'Perlu Perbaikan',
        };
    }

    /**
     * Batas bawah tiap kategori dibaca dari system setting
     * `hr.performance_thresholds` kalau ada, jadi tiap universitas bisa
     * menyesuaikan tanpa ubah kode — default di bawah dipakai kalau belum
     * dikonfigurasi.
     *
     * @param  array{very_good?: int|float, good?: int|float, fair?: int|float}  $thresholds
     */
    public static function fromScore(float $score, array $thresholds = []): self
    {
        $veryGood = (float) ($thresholds['very_good'] ?? 90);
        $good = (float) ($thresholds['good'] ?? 76);
        $fair = (float) ($thresholds['fair'] ?? 61);

        return match (true) {
            $score >= $veryGood => self::VeryGood,
            $score >= $good => self::Good,
            $score >= $fair => self::Fair,
            default => self::NeedsImprovement,
        };
    }
}
