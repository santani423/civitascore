<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\AllocationMethod;
use Modules\Admission\Enums\SchemeStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $code
 * @property string $name
 * @property int $version
 * @property SchemeStatus $status
 * @property string|null $passing_score
 * @property AllocationMethod $allocation_method
 * @property array<mixed> $tie_break_rules
 * @property int $rounding_scale
 * @property string|null $based_on_id
 * @property CarbonImmutable|null $locked_at
 * @property string|null $locked_by
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, SelectionComponent> $components
 */
class SelectionScheme extends PmbModel
{
    protected $table = 'pmb_selection_schemes';

    protected function casts(): array
    {
        return [
            'status' => SchemeStatus::class,
            'passing_score' => 'decimal:2',
            'allocation_method' => AllocationMethod::class,
            'tie_break_rules' => 'array',
            'rounding_scale' => 'integer',
            'version' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SelectionComponent, $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(SelectionComponent::class, 'selection_scheme_id')->orderBy('sort_order');
    }
}
