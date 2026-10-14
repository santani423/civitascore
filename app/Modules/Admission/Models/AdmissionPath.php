<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admission\Enums\PathCategory;

/**
 * @property string $id
 * @property string $university_id
 * @property string $code
 * @property string $name
 * @property PathCategory $category
 * @property string|null $description
 * @property array<mixed> $eligible_degree_levels
 * @property bool $requires_exam
 * @property bool $requires_previous_institution
 * @property bool $is_active
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Collection<int, WavePath> $wavePaths
 */
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
