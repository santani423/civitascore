<?php

namespace Modules\Academic\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Models\University;

/**
 * Sesi presensi mandiri untuk satu pertemuan kelas. Dosen membukanya, lalu
 * mahasiswa memindai QR yang dibuat dari `secret` sesi dan berganti setiap
 * TOKEN_TTL_SECONDS (lihat AttendanceCheckInService). `secret` tidak pernah
 * keluar dari server.
 *
 * @property string $id
 * @property string $university_id
 * @property string $class_section_id
 * @property int $meeting_number
 * @property CarbonImmutable $meeting_date
 * @property string $secret
 * @property string|null $opened_by
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $closed_at
 * @property-read ClassSection $classSection
 */
class AttendanceSession extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'class_section_id', 'meeting_number', 'meeting_date',
        'secret', 'opened_by', 'expires_at', 'closed_at',
    ];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'meeting_number' => 'integer',
            'meeting_date' => 'date',
            'expires_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
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
     * @return BelongsTo<ClassSection, $this>
     */
    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function isOpen(CarbonImmutable $now): bool
    {
        return $this->closed_at === null && $this->expires_at->greaterThan($now);
    }
}
