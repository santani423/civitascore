<?php

namespace Modules\Alumni\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Student;
use Modules\Alumni\Database\Factories\AlumniFactory;
use Modules\Alumni\Enums\AlumniEmploymentStatus;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $student_id
 * @property int $graduation_year
 * @property AlumniEmploymentStatus $employment_status
 * @property string|null $company_name
 * @property string|null $job_title
 * @property int|null $waiting_period_months
 * @property bool $is_verified
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 */
class Alumni extends Model implements ScopesToInstitution
{
    /** @use HasFactory<AlumniFactory> */
    use HasFactory, HasUlids, TenantScoped;

    // "Alumni" is already plural/collective — Eloquent's pluralizer would
    // otherwise guess something other than the actual "alumni" table name,
    // so it's pinned explicitly (see Curriculum for the same gotcha).
    protected $table = 'alumni';

    protected $fillable = [
        'university_id', 'student_id', 'graduation_year', 'employment_status',
        'company_name', 'job_title', 'waiting_period_months', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
            'employment_status' => AlumniEmploymentStatus::class,
            'waiting_period_months' => 'integer',
            'is_verified' => 'boolean',
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
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    protected static function newFactory(): AlumniFactory
    {
        return AlumniFactory::new();
    }
}
