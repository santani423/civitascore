<?php

namespace Modules\Academic\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academic\Database\Factories\EmployeeFactory;
use Modules\AuditLog\Support\Auditable;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Enums\Gender;
use Modules\HumanResource\Enums\StaffCategory;
use Modules\HumanResource\Models\EmployeeCertification;
use Modules\HumanResource\Models\EmployeeContract;
use Modules\HumanResource\Models\EmployeeDocument;
use Modules\HumanResource\Models\EmployeeEducation;
use Modules\HumanResource\Models\EmployeePosition;
use Modules\HumanResource\Models\EmployeeRank;
use Modules\HumanResource\Models\EmployeeTraining;
use Modules\HumanResource\Models\EmployeeTransfer;
use Modules\HumanResource\Models\LeaveRequest;
use Modules\HumanResource\Models\LecturerAcademicRank;
use Modules\HumanResource\Models\LecturerActivity;
use Modules\HumanResource\Models\PerformanceReview;
use Modules\HumanResource\Models\Position;
use Modules\HumanResource\Models\Rank;
use Modules\HumanResource\Models\WorkUnit;
use Modules\Tenancy\Models\University;

/**
 * Master seluruh pegawai universitas — dosen (employee_type = lecturer,
 * terhubung 1:1 ke `lecturers` lewat lecturers.employee_id) maupun tenaga
 * kependidikan. Kolom `unit_kerja`/`position` (teks) adalah kolom lama yang
 * dibaca Dashboard/Laporan; Modul SDM menjaganya tetap sinkron dengan
 * master work_units/positions.
 *
 * @property string $id
 * @property string $university_id
 * @property string|null $user_id
 * @property EmployeeType $employee_type
 * @property string|null $nik
 * @property string|null $nip
 * @property string $unit_kerja
 * @property string $name
 * @property string|null $email
 * @property string|null $position
 * @property Gender|null $gender
 * @property string|null $birth_place
 * @property CarbonImmutable|null $birth_date
 * @property string|null $phone
 * @property string|null $address
 * @property EmploymentStatus $employment_status
 * @property string|null $work_unit_id
 * @property string|null $position_id
 * @property string|null $rank_id
 * @property string|null $faculty_id
 * @property string|null $study_program_id
 * @property EducationLevel|null $highest_education
 * @property StaffCategory|null $staff_category
 * @property CarbonImmutable|null $joined_at
 * @property string|null $inactive_reason
 * @property CarbonImmutable|null $inactive_at
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User|null $user
 * @property-read Lecturer|null $lecturer
 * @property-read WorkUnit|null $workUnit
 * @property-read Position|null $jobPosition
 * @property-read Rank|null $rank
 * @property-read Faculty|null $faculty
 * @property-read StudyProgram|null $studyProgram
 */
class Employee extends Model implements ScopesToInstitution
{
    /** @use HasFactory<EmployeeFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'university_id', 'user_id', 'employee_type', 'nik', 'nip', 'unit_kerja', 'name', 'email', 'position',
        'gender', 'birth_place', 'birth_date', 'phone', 'address', 'employment_status',
        'work_unit_id', 'position_id', 'rank_id', 'faculty_id', 'study_program_id',
        'highest_education', 'staff_category', 'joined_at', 'inactive_reason', 'inactive_at', 'is_active',
    ];

    /**
     * NIK tidak ikut serialisasi default maupun audit log (AuditLogService
     * membuang atribut $hidden) — hanya ditampilkan eksplisit oleh
     * resource detail SDM.
     *
     * @var list<string>
     */
    protected $hidden = ['nik'];

    /**
     * Sama dengan default kolom di migration — supaya instance yang baru
     * dibuat (sebelum di-refresh dari DB) sudah punya nilai enum yang valid.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'employee_type' => 'staff',
        'employment_status' => 'permanent',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'employee_type' => EmployeeType::class,
            'employment_status' => EmploymentStatus::class,
            'gender' => Gender::class,
            'highest_education' => EducationLevel::class,
            'staff_category' => StaffCategory::class,
            'birth_date' => 'immutable_date',
            'joined_at' => 'immutable_date',
            'inactive_at' => 'immutable_date',
            'is_active' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<Lecturer, $this>
     */
    public function lecturer(): HasOne
    {
        return $this->hasOne(Lecturer::class);
    }

    /**
     * @return BelongsTo<WorkUnit, $this>
     */
    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    /**
     * Bukan `position()` — nama itu sudah dipakai kolom teks lama `position`.
     *
     * @return BelongsTo<Position, $this>
     */
    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * @return BelongsTo<Rank, $this>
     */
    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    /**
     * @return BelongsTo<Faculty, $this>
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * @return HasMany<EmployeeEducation, $this>
     */
    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    /**
     * @return HasMany<EmployeePosition, $this>
     */
    public function positionHistories(): HasMany
    {
        return $this->hasMany(EmployeePosition::class);
    }

    /**
     * @return HasMany<EmployeeRank, $this>
     */
    public function rankHistories(): HasMany
    {
        return $this->hasMany(EmployeeRank::class);
    }

    /**
     * @return HasMany<LecturerAcademicRank, $this>
     */
    public function academicRankHistories(): HasMany
    {
        return $this->hasMany(LecturerAcademicRank::class);
    }

    /**
     * @return HasMany<LecturerActivity, $this>
     */
    public function lecturerActivities(): HasMany
    {
        return $this->hasMany(LecturerActivity::class);
    }

    /**
     * @return HasMany<EmployeeContract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    /**
     * @return HasMany<EmployeeDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * @return HasMany<EmployeeTraining, $this>
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    /**
     * @return HasMany<EmployeeCertification, $this>
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(EmployeeCertification::class);
    }

    /**
     * @return HasMany<PerformanceReview, $this>
     */
    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    /**
     * @return HasMany<EmployeeTransfer, $this>
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(EmployeeTransfer::class);
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function isLecturer(): bool
    {
        return $this->employee_type === EmployeeType::Lecturer;
    }

    /**
     * Hanya tenaga kependidikan. Sebelum Modul SDM, tabel ini memang hanya
     * berisi tendik — fitur lama yang menghitung/menampilkan "Pegawai"
     * (menu Pegawai, Dashboard, Laporan, statistik platform) memakai scope
     * ini supaya dosen (yang kini juga punya baris di sini) tidak terhitung
     * dua kali.
     *
     * @param  Builder<Employee>  $query
     */
    #[Scope]
    protected function educationStaff(Builder $query): void
    {
        $query->where('employee_type', EmployeeType::Staff);
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }
}
