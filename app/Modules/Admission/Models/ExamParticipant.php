<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admission\Enums\AttendanceStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $schedule_id
 * @property string $session_id
 * @property string $application_id
 * @property string $participant_number
 * @property AttendanceStatus $attendance_status
 * @property CarbonImmutable|null $checked_in_at
 * @property string|null $checked_in_by
 * @property int $reschedule_count
 * @property string|null $card_file_id
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExamSchedule $schedule
 * @property-read ExamSession $session
 * @property-read Application $application
 * @property-read CbtAttempt|null $cbtAttempt
 */
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
