<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $participant_id
 * @property array<mixed> $question_order
 * @property array<mixed>|null $answers
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable $deadline_at
 * @property CarbonImmutable|null $submitted_at
 * @property string|null $raw_score
 * @property int $violations
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExamParticipant $participant
 */
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
