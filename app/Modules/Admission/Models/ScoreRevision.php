<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ScoreSource;

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
