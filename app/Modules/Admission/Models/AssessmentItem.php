<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\AssessmentItemStatus;
use Modules\Admission\Enums\AssessmentQuestionType;

/**
 * Butir pertanyaan (bank soal). `is_reversed` dan `dimension_id` adalah data penilaian dan
 * tidak pernah dikirim ke peserta. `source_item_id` menelusuri asal butir hasil salin.
 *
 * @property string $id
 * @property string $university_id
 * @property string $version_id
 * @property string $dimension_id
 * @property string|null $code
 * @property AssessmentQuestionType $question_type
 * @property AssessmentItemStatus $status
 * @property string $stem
 * @property bool $is_reversed
 * @property int $sort_order
 * @property string|null $source_item_id
 * @property CarbonImmutable|null $created_at
 * @property-read AssessmentVersion $version
 * @property-read AssessmentDimension $dimension
 * @property-read HasMany<AssessmentResponse, $this> $responses
 */
class AssessmentItem extends PmbModel
{
    protected $table = 'pmb_assessment_items';

    protected function casts(): array
    {
        return [
            'is_reversed' => 'boolean',
            'sort_order' => 'integer',
            'question_type' => AssessmentQuestionType::class,
            'status' => AssessmentItemStatus::class,
        ];
    }

    /**
     * @return BelongsTo<AssessmentVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'version_id');
    }

    /**
     * @return BelongsTo<AssessmentDimension, $this>
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(AssessmentDimension::class, 'dimension_id');
    }

    /**
     * @return HasMany<AssessmentResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(AssessmentResponse::class, 'item_id');
    }
}
