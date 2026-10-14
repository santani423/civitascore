<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $university_id
 * @property string $code
 * @property string $name
 * @property string|null $schedule_note
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class StudySystem extends PmbModel
{
    use SoftDeletes;

    protected $table = 'pmb_study_systems';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
