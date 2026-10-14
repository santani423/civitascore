<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ScoreSource;

/**
 * @property string $id
 * @property string $university_id
 * @property string $selection_score_id
 * @property string|null $old_raw_score
 * @property string|null $new_raw_score
 * @property bool|null $old_passed
 * @property bool|null $new_passed
 * @property string $reason
 * @property ScoreSource $source
 * @property string|null $approval_request_id
 * @property string|null $changed_by
 * @property CarbonImmutable|null $created_at
 * @property-read SelectionScore $score
 */
class ScoreRevision extends PmbModel
{
    protected $table = 'pmb_score_revisions';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'old_raw_score' => 'decimal:4',
            'new_raw_score' => 'decimal:4',
            'old_passed' => 'boolean',
            'new_passed' => 'boolean',
            'source' => ScoreSource::class,
        ];
    }

    /**
     * @return BelongsTo<SelectionScore, $this>
     */
    public function score(): BelongsTo
    {
        return $this->belongsTo(SelectionScore::class, 'selection_score_id');
    }
}
