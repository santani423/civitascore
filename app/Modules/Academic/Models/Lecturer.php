<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\Factories\LecturerFactory;
use Modules\AuditLog\Support\Auditable;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\LecturerStatus;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string|null $faculty_id
 * @property string|null $employee_id
 * @property string|null $study_program_id
 * @property string $nidn
 * @property string|null $nidk
 * @property string $name
 * @property string|null $email
 * @property string|null $serdos_number
 * @property AcademicRank|null $academic_rank
 * @property string|null $expertise
 * @property LecturerStatus|null $lecturer_status
 * @property CarbonImmutable|null $teaching_started_at
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Faculty|null $faculty
 * @property-read Employee|null $employee
 * @property-read StudyProgram|null $studyProgram
 */
class Lecturer extends Model implements ScopesToInstitution
{
    /** @use HasFactory<LecturerFactory> */
    use Auditable, HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'faculty_id', 'employee_id', 'study_program_id', 'nidn', 'nidk', 'name', 'email',
        'serdos_number', 'academic_rank', 'expertise', 'lecturer_status', 'teaching_started_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'academic_rank' => AcademicRank::class,
            'lecturer_status' => LecturerStatus::class,
            'teaching_started_at' => 'immutable_date',
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
     * Data kepegawaian dosen ini (Modul SDM) — lihat Employee.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Program studi homebase.
     *
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * Kelas yang diampu dosen ini.
     *
     * @return HasMany<ClassSection, $this>
     */
    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }

    /**
     * Mahasiswa perwalian (dosen ini sebagai dosen wali).
     *
     * @return HasMany<Student, $this>
     */
    public function advisees(): HasMany
    {
        return $this->hasMany(Student::class, 'academic_advisor_id');
    }

    protected static function newFactory(): LecturerFactory
    {
        return LecturerFactory::new();
    }
}
