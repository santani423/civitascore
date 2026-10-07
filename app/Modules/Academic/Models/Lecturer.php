<?php

namespace Modules\Academic\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academic\Database\Factories\LecturerFactory;
use Modules\Academic\Enums\LecturerEducationLevel;
use Modules\Academic\Enums\LecturerEmploymentStatus;
use Modules\Academic\Enums\LecturerFunctionalRank;
use Modules\AuditLog\Support\Auditable;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string|null $user_id
 * @property string|null $faculty_id
 * @property string $nidn
 * @property string|null $nip
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property LecturerEmploymentStatus|null $employment_status
 * @property LecturerFunctionalRank|null $functional_rank
 * @property LecturerEducationLevel|null $highest_education
 * @property CarbonImmutable|null $hired_at
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Faculty|null $faculty
 * @property-read User|null $user
 */
class Lecturer extends Model implements ScopesToInstitution
{
    /** @use HasFactory<LecturerFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'university_id', 'user_id', 'faculty_id', 'nidn', 'nip', 'name', 'email', 'phone',
        'employment_status', 'functional_rank', 'highest_education', 'hired_at', 'is_active',
    ];

    /**
     * Mirrors the column default so a freshly created model (not re-read
     * from the DB) reports is_active correctly — LecturerAccountService
     * copies it onto the new login account.
     *
     * @var array<string, mixed>
     */
    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'employment_status' => LecturerEmploymentStatus::class,
            'functional_rank' => LecturerFunctionalRank::class,
            'highest_education' => LecturerEducationLevel::class,
            'hired_at' => 'date',
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
     * @return BelongsTo<Faculty, $this>
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * The login identity of this lecturer (see LecturerAccountService), null
     * until an account has been provisioned.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): LecturerFactory
    {
        return LecturerFactory::new();
    }
}
