<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\Factories\AttendanceFactory;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $krs_item_id
 * @property int $meeting_number
 * @property CarbonImmutable $meeting_date
 * @property AttendanceStatus $status
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read KrsItem $krsItem
 */
class Attendance extends Model implements ScopesToInstitution
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'krs_item_id', 'meeting_number', 'meeting_date', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'meeting_number' => 'integer',
            'meeting_date' => 'date',
            'status' => AttendanceStatus::class,
        ];
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return BelongsTo<KrsItem, $this>
     */
    public function krsItem(): BelongsTo
    {
        return $this->belongsTo(KrsItem::class);
    }

    protected static function newFactory(): AttendanceFactory
    {
        return AttendanceFactory::new();
    }
}
