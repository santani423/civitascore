<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admission\Enums\AttendanceStatus;

class ExamParticipant extends PmbModel
{
    protected $table = 'pmb_exam_participants';

    protected function casts(): array
    {
        return [
            'attendance_status' => AttendanceStatus::class,
            'checked_in_at' => 'datetime',
            'reschedule_count' => 'integer',
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
     * @return BelongsTo<ExamSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return HasOne<CbtAttempt, $this>
     */
    public function cbtAttempt(): HasOne
    {
        return $this->hasOne(CbtAttempt::class, 'participant_id');
    }
}
