<?php

namespace Modules\Scholarship\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Student;
use Modules\Scholarship\Database\Factories\ScholarshipApplicationFactory;
use Modules\Scholarship\Enums\ScholarshipApplicationStatus;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $scholarship_id
 * @property string $student_id
 * @property ScholarshipApplicationStatus $status
 * @property CarbonImmutable $submitted_at
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read University $university
 * @property-read Scholarship $scholarship
 * @property-read Student $student
 */
class ScholarshipApplication extends Model implements ScopesToInstitution
{
    /** @use HasFactory<ScholarshipApplicationFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'scholarship_id', 'student_id', 'status', 'submitted_at', 'reviewed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScholarshipApplicationStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
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
     * @return BelongsTo<Scholarship, $this>
     */
    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    protected static function newFactory(): ScholarshipApplicationFactory
    {
        return ScholarshipApplicationFactory::new();
    }
}
