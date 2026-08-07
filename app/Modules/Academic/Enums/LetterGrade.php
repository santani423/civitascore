<?php

namespace Modules\Academic\Enums;

enum LetterGrade: string
{
    case A = 'A';
    case AB = 'AB';
    case B = 'B';
    case BC = 'BC';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    /** Bobot IP standar (skala 4,00) dipakai untuk perhitungan IP/IPK. */
    public function weight(): float
    {
        return match ($this) {
            self::A => 4.00,
            self::AB => 3.50,
            self::B => 3.00,
            self::BC => 2.50,
            self::C => 2.00,
            self::D => 1.00,
            self::E => 0.00,
        };
    }

    /** Konversi skor 0-100 ke nilai huruf memakai batas ambang standar. */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 85 => self::A,
            $score >= 75 => self::AB,
            $score >= 65 => self::B,
            $score >= 55 => self::BC,
            $score >= 45 => self::C,
            $score >= 35 => self::D,
            default => self::E,
        };
    }
}
