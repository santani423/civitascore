<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ComponentSource;
use Modules\Admission\Enums\NormalizationMethod;
use Modules\Admission\Enums\ScoreType;

class SelectionComponent extends PmbModel
{
    protected $table = 'pmb_selection_components';

    protected function casts(): array
    {
        return [
            'source' => ComponentSource::class,
            'score_type' => ScoreType::class,
            'raw_min' => 'decimal:4',
            'raw_max' => 'decimal:4',
            'weight' => 'decimal:4',
            'minimum_normalized' => 'decimal:2',
            'is_mandatory' => 'boolean',
            'tie_break_priority' => 'integer',
            'normalization' => NormalizationMethod::class,
            'rubric' => 'array',
            'config' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SelectionScheme, $this>
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(SelectionScheme::class, 'selection_scheme_id');
    }
}
