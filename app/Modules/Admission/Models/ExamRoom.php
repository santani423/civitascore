<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamRoom extends PmbModel
{
    use SoftDeletes;

    protected $table = 'pmb_exam_rooms';

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_accessible' => 'boolean',
            'facilities' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ExamVenue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(ExamVenue::class, 'venue_id');
    }
}
