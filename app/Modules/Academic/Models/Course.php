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
use Modules\Academic\Database\Factories\CourseFactory;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $study_program_id
 * @property string $curriculum_id
 * @property string $code
 * @property string $name
 * @property int $credits
 * @property int $semester_level
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read StudyProgram $studyProgram
 * @property-read Curriculum $curriculum
 */
class Course extends Model implements ScopesToInstitution
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'study_program_id', 'curriculum_id',
        'code', 'name', 'credits', 'semester_level', 'is_active',
    ];

    protected function casts(): array
    {
        return ['credits' => 'integer', 'semester_level' => 'integer', 'is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * @return BelongsTo<Curriculum, $this>
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    /**
     * @return HasMany<ClassSection, $this>
     */
    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }
}
