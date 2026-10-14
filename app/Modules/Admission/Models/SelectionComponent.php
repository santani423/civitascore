<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ComponentSource;
use Modules\Admission\Enums\NormalizationMethod;
use Modules\Admission\Enums\ScoreType;

/**
 * @property string $id
 * @property string $university_id
 * @property string $selection_scheme_id
 * @property string $code
 * @property string $name
 * @property ComponentSource $source
 * @property ScoreType $score_type
 * @property string|null $raw_min
 * @property string|null $raw_max
 * @property string $weight
 * @property string|null $minimum_normalized
 * @property bool $is_mandatory
 * @property int|null $tie_break_priority
 * @property NormalizationMethod $normalization
 * @property array<mixed>|null $rubric
 * @property array<mixed>|null $config
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read SelectionScheme $scheme
 */
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
