<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;

/**
 * @extends Factory<KrsItem>
 */
class KrsItemFactory extends Factory
{
    protected $model = KrsItem::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'class_section_id' => ClassSection::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'status' => KrsItemStatus::Enrolled,
        ];
    }
}
