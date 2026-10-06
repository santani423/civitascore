<?php

namespace Modules\HumanResource\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\AuditLog\Support\Auditable;
use Modules\HumanResource\Database\Factories\WorkUnitFactory;
use Modules\HumanResource\Enums\WorkUnitType;

/**
 * @property string $id
 * @property string $university_id
 * @property string|null $parent_id
 * @property string|null $faculty_id
 * @property string|null $study_program_id
 * @property string $code
 * @property string $name
 * @property WorkUnitType $type
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read WorkUnit|null $parent
 * @property-read Faculty|null $faculty
 * @property-read StudyProgram|null $studyProgram
 */
class WorkUnit extends Model implements ScopesToInstitution
{
    /** @use HasFactory<WorkUnitFactory> */
    use Auditable, HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'parent_id', 'faculty_id', 'study_program_id', 'code', 'name', 'type', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => WorkUnitType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<WorkUnit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'parent_id');
    }

    /**
     * @return HasMany<WorkUnit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(WorkUnit::class, 'parent_id');
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
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    protected static function newFactory(): WorkUnitFactory
    {
        return WorkUnitFactory::new();
    }
}
