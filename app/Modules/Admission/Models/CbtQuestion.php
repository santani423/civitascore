<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $schedule_id
 * @property string $question
 * @property array<mixed> $options
 * @property string $correct_option
 * @property string $points
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExamSchedule $schedule
 */
class CbtQuestion extends PmbModel
{
    protected $table = 'pmb_cbt_questions';

    /** @var array<int, string> */
    protected $hidden = ['correct_option'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'points' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ExamSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'schedule_id');
    }
}
