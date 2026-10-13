<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtAttempt extends PmbModel
{
    protected $table = 'pmb_cbt_attempts';

    protected function casts(): array
    {
        return [
            'question_order' => 'array',
            'answers' => 'array',
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'raw_score' => 'decimal:4',
            'violations' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ExamParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExamParticipant::class, 'participant_id');
    }
}
