<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dimensi skor dalam satu versi (mis. R, I, A, S, E, C untuk RIASEC).
 * `interpretation` diisi oleh tim asesmen; tidak ada teks interpretasi bawaan.
 *
 * @property string $id
 * @property string $university_id
 * @property string $version_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string|null $interpretation
 * @property int $sort_order
 * @property-read AssessmentVersion $version
 * @property-read HasMany<AssessmentItem, $this> $items
 */
class AssessmentDimension extends PmbModel
{
    protected $table = 'pmb_assessment_dimensions';

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<AssessmentVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'version_id');
    }

    /**
     * @return HasMany<AssessmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AssessmentItem::class, 'dimension_id');
    }
}
