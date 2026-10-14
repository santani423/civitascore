<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $university_id
 * @property string $venue_id
 * @property string $code
 * @property string $name
 * @property int $capacity
 * @property bool $is_accessible
 * @property array<mixed>|null $facilities
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read ExamVenue $venue
 */
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
