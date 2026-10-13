<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreImport extends PmbModel
{
    protected $table = 'pmb_score_imports';

    protected function casts(): array
    {
        return [
            'valid_rows' => 'array',
            'errors' => 'array',
            'committed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ExamSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'schedule_id');
    }

    /**
     * @return BelongsTo<SelectionComponent, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(SelectionComponent::class, 'selection_component_id');
    }
}
