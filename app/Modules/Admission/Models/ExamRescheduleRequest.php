<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\RescheduleRequestStatus;
use Modules\FileManagement\Models\FileUpload;

/**
 * @property string $id
 * @property string $university_id
 * @property string $participant_id
 * @property string $reason
 * @property string|null $attachment_file_id
 * @property RescheduleRequestStatus $status
 * @property string|null $target_session_id
 * @property string|null $decision_note
 * @property string|null $decided_by
 * @property CarbonImmutable|null $decided_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExamParticipant $participant
 * @property-read FileUpload|null $attachment
 * @property-read ExamSession|null $targetSession
 */
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
     * @return BelongsTo<FileUpload, $this>
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'attachment_file_id');
    }

    /**
     * @return BelongsTo<ExamSession, $this>
     */
    public function targetSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'target_session_id');
    }
}
