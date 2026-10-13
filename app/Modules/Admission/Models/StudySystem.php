<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

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
