<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamVenue extends PmbModel
{
    use SoftDeletes;

    protected $table = 'pmb_exam_venues';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ExamRoom, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(ExamRoom::class, 'venue_id');
    }
}
