<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Skor satu dimensi pada satu sesi. Nilai mentah, rata-rata, dan persentase
 * dari rentang skala; bukan skor terstandar (belum ada norma yang disahkan).
 *
 * @property string $id
 * @property string $university_id
 * @property string $session_id
 * @property string $dimension_id
 * @property string $dimension_code
 * @property int $item_count
 * @property float $raw_score
 * @property float $mean_score
 * @property float $percent_score
 * @property int $rank
 * @property-read AssessmentSession $session
 * @property-read AssessmentDimension $dimension
 */
class AssessmentScore extends PmbModel
{
    protected $table = 'pmb_assessment_scores';

    protected function casts(): array
    {
        return [
            'item_count' => 'integer',
            'raw_score' => 'float',
            'mean_score' => 'float',
            'percent_score' => 'float',
            'rank' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AssessmentSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'session_id');
    }

    /**
     * @return BelongsTo<AssessmentDimension, $this>
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(AssessmentDimension::class, 'dimension_id');
    }
}
