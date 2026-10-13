<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\ExamMode;
use Modules\Admission\Enums\ExamScheduleStatus;

class ExamSchedule extends PmbModel
{
    protected $table = 'pmb_exam_schedules';

    protected function casts(): array
    {
        return [
            'wave_path_ids' => 'array',
            'mode' => ExamMode::class,
            'status' => ExamScheduleStatus::class,
            'exam_date' => 'date',
            'published_at' => 'datetime',
            'cbt_duration_minutes' => 'integer',
            'cbt_question_count' => 'integer',
            'cbt_randomize' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Wave, $this>
     */
    public function wave(): BelongsTo
    {
        return $this->belongsTo(Wave::class, 'wave_id');
    }

    /**
     * @return BelongsTo<SelectionComponent, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(SelectionComponent::class, 'selection_component_id');
    }

    /**
     * @return HasMany<ExamSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'schedule_id')->orderBy('starts_at');
    }

    /**
     * @return HasMany<ExamParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class, 'schedule_id');
    }

    /**
     * @return HasMany<CbtQuestion, $this>
     */
    public function cbtQuestions(): HasMany
    {
        return $this->hasMany(CbtQuestion::class, 'schedule_id')->orderBy('sort_order');
    }
}
