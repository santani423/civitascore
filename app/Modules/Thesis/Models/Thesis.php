<?php

namespace Modules\Thesis\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Thesis\Database\Factories\ThesisFactory;
use Modules\Thesis\Enums\ThesisStatus;
use Modules\Thesis\Enums\ThesisType;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $student_id
 * @property string|null $supervisor_lecturer_id
 * @property string $title
 * @property ThesisType $thesis_type
 * @property ThesisStatus $status
 * @property CarbonImmutable $submitted_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 * @property-read Lecturer|null $supervisor
 */
class Thesis extends Model implements ScopesToInstitution
{
    /** @use HasFactory<ThesisFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'student_id', 'supervisor_lecturer_id', 'title',
        'thesis_type', 'status', 'submitted_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'thesis_type' => ThesisType::class,
            'status' => ThesisStatus::class,
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
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

    protected static function newFactory(): ThesisFactory
    {
        return ThesisFactory::new();
    }
}
