<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\RescheduleRequestStatus;

class ExamRescheduleRequest extends PmbModel
{
    protected $table = 'pmb_exam_reschedule_requests';

    protected function casts(): array
    {
        return [
            'status' => RescheduleRequestStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ExamParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExamParticipant::class, 'participant_id');
    }

    /**
     * @return BelongsTo<ExamSession, $this>
     */
    public function targetSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'target_session_id');
    }
}
