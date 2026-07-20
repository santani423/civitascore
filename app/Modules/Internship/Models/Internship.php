<?php

namespace Modules\Internship\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Internship\Database\Factories\InternshipFactory;
use Modules\Internship\Enums\InternshipProgramType;
use Modules\Internship\Enums\InternshipStatus;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $student_id
 * @property InternshipProgramType $program_type
 * @property string $institution_name
 * @property string|null $position
 * @property string|null $supervisor_lecturer_id
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property InternshipStatus $status
 * @property int|null $sks_converted
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 * @property-read Lecturer|null $supervisor
 */
class Internship extends Model implements ScopesToInstitution
{
    /** @use HasFactory<InternshipFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'student_id', 'program_type', 'institution_name', 'position',
        'supervisor_lecturer_id', 'start_date', 'end_date', 'status', 'sks_converted',
    ];

    protected function casts(): array
    {
        return [
            'program_type' => InternshipProgramType::class,
            'status' => InternshipStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'sks_converted' => 'integer',
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

    /**
     * @return BelongsTo<Lecturer, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'supervisor_lecturer_id');
    }

    protected static function newFactory(): InternshipFactory
    {
        return InternshipFactory::new();
    }
}
