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
use Modules\Academic\Database\Factories\AcademicCalendarEventFactory;
use Modules\Academic\Enums\AcademicCalendarCategory;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string|null $academic_term_id
 * @property string|null $study_program_id
 * @property string $title
 * @property string|null $description
 * @property AcademicCalendarCategory $category
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AcademicTerm|null $academicTerm
 * @property-read StudyProgram|null $studyProgram
 */
class AcademicCalendarEvent extends Model implements ScopesToInstitution
{
    /** @use HasFactory<AcademicCalendarEventFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'academic_term_id', 'study_program_id', 'title', 'description',
        'category', 'start_date', 'end_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => AcademicCalendarCategory::class,
            'start_date' => 'date',
            'end_date' => 'date',
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
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): AcademicCalendarEventFactory
    {
        return AcademicCalendarEventFactory::new();
    }
}
