<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $university_id
 * @property string $name
 * @property string|null $address
 * @property string|null $map_url
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Collection<int, ExamRoom> $rooms
 */
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
