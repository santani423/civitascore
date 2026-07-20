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
use Modules\Academic\Database\Factories\CurriculumFactory;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $study_program_id
 * @property string $name
 * @property string $academic_year
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read StudyProgram $studyProgram
 */
class Curriculum extends Model implements ScopesToInstitution
{
    /** @use HasFactory<CurriculumFactory> */
    use HasFactory, HasUlids, TenantScoped;

    // Eloquent's default pluralizer guesses "curricula" for this class name
    // (correct English plural) — the migration/routes/API all use
    // "curriculums" instead, so the table name is pinned explicitly.
    protected $table = 'curriculums';

    protected $fillable = ['university_id', 'study_program_id', 'name', 'academic_year', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    protected static function newFactory(): CurriculumFactory
    {
        return CurriculumFactory::new();
    }
}
