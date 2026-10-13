<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
