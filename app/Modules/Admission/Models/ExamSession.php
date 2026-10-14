<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $university_id
 * @property string $schedule_id
 * @property string|null $room_id
 * @property string $code
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property CarbonImmutable|null $checkin_opens_at
 * @property int $capacity
 * @property int $participants_count
 * @property string|null $online_url
 * @property CarbonImmutable|null $scores_locked_at
 * @property string|null $scores_locked_by
 * @property int $lock_version
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExamSchedule $schedule
 * @property-read ExamRoom|null $room
 * @property-read Collection<int, ExamSessionExaminer> $examiners
 * @property-read Collection<int, ExamParticipant> $participants
 */
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
