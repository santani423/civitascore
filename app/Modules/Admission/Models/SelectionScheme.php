<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\AllocationMethod;
use Modules\Admission\Enums\SchemeStatus;

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
