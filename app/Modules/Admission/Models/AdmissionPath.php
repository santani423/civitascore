<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admission\Enums\PathCategory;

class AdmissionPath extends PmbModel
{
    use SoftDeletes;

    protected $table = 'pmb_admission_paths';

    protected function casts(): array
    {
        return [
            'category' => PathCategory::class,
            'eligible_degree_levels' => 'array',
            'requires_exam' => 'boolean',
            'requires_previous_institution' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<WavePath, $this>
     */
    public function wavePaths(): HasMany
    {
        return $this->hasMany(WavePath::class, 'admission_path_id');
    }
}
