<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Models\AcademicTerm;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    protected $model = AcademicTerm::class;

    public function definition(): array
    {
        return [
            'academic_year' => '2026/2027',
            'semester' => AcademicSemester::Ganjil,
            'is_current' => false,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(4),
        ];
    }
}
