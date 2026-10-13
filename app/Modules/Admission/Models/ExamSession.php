<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends PmbModel
{
    protected $table = 'pmb_exam_sessions';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'checkin_opens_at' => 'datetime',
            'capacity' => 'integer',
            'participants_count' => 'integer',
            'scores_locked_at' => 'datetime',
            'lock_version' => 'integer',
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
     * @return BelongsTo<ExamRoom, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(ExamRoom::class, 'room_id');
    }

    /**
     * @return HasMany<ExamSessionExaminer, $this>
     */
    public function examiners(): HasMany
    {
        return $this->hasMany(ExamSessionExaminer::class, 'session_id');
    }

    /**
     * @return HasMany<ExamParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class, 'session_id');
    }
}
