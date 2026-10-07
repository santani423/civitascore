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
<<<<<<< HEAD
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academic\Database\Factories\LecturerFactory;
use Modules\Academic\Enums\LecturerEducationLevel;
use Modules\Academic\Enums\LecturerEmploymentStatus;
use Modules\Academic\Enums\LecturerFunctionalRank;
use Modules\AuditLog\Support\Auditable;
=======
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\Factories\LecturerFactory;
use Modules\AuditLog\Support\Auditable;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\LecturerStatus;
>>>>>>> feature/sdm
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string|null $user_id
 * @property string|null $faculty_id
 * @property string|null $employee_id
 * @property string|null $study_program_id
 * @property string $nidn
<<<<<<< HEAD
 * @property string|null $nip
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property LecturerEmploymentStatus|null $employment_status
 * @property LecturerFunctionalRank|null $functional_rank
 * @property LecturerEducationLevel|null $highest_education
 * @property CarbonImmutable|null $hired_at
=======
 * @property string|null $nidk
 * @property string $name
 * @property string|null $email
 * @property string|null $serdos_number
 * @property AcademicRank|null $academic_rank
 * @property string|null $expertise
 * @property LecturerStatus|null $lecturer_status
 * @property CarbonImmutable|null $teaching_started_at
>>>>>>> feature/sdm
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Faculty|null $faculty
<<<<<<< HEAD
 * @property-read User|null $user
=======
 * @property-read Employee|null $employee
 * @property-read StudyProgram|null $studyProgram
>>>>>>> feature/sdm
 */
class Lecturer extends Model implements ScopesToInstitution
{
    /** @use HasFactory<LecturerFactory> */
<<<<<<< HEAD
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
=======
    use Auditable, HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'faculty_id', 'employee_id', 'study_program_id', 'nidn', 'nidk', 'name', 'email',
        'serdos_number', 'academic_rank', 'expertise', 'lecturer_status', 'teaching_started_at', 'is_active',
    ];
>>>>>>> feature/sdm

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
<<<<<<< HEAD
            'employment_status' => LecturerEmploymentStatus::class,
            'functional_rank' => LecturerFunctionalRank::class,
            'highest_education' => LecturerEducationLevel::class,
            'hired_at' => 'date',
=======
            'academic_rank' => AcademicRank::class,
            'lecturer_status' => LecturerStatus::class,
            'teaching_started_at' => 'immutable_date',
>>>>>>> feature/sdm
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
<<<<<<< HEAD
     * The login identity of this lecturer (see LecturerAccountService), null
     * until an account has been provisioned.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
=======
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
>>>>>>> feature/sdm
    }

    protected static function newFactory(): LecturerFactory
    {
        return LecturerFactory::new();
    }
}
