<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\Factories\ExamFactory;
use Modules\Academic\Enums\QuestionSelectionMode;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $class_section_id
 * @property string $title
 * @property int $duration_minutes
 * @property int $questions_per_participant
 * @property QuestionSelectionMode $question_selection_mode
 * @property bool $randomize_questions
 * @property bool $randomize_options
 * @property bool $allow_back_navigation
 * @property bool $show_result_after_submission
 * @property int $max_attempts
 * @property bool $is_published
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ClassSection $classSection
 * @property-read Collection<int, ExamQuestion> $questions
 * @property-read Collection<int, ExamAttempt> $attempts
 */
class Exam extends Model implements ScopesToInstitution
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'class_section_id', 'title', 'duration_minutes',
        'questions_per_participant', 'question_selection_mode',
        'randomize_questions', 'randomize_options', 'allow_back_navigation',
        'show_result_after_submission', 'max_attempts', 'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'questions_per_participant' => 'integer',
            'question_selection_mode' => QuestionSelectionMode::class,
            'randomize_questions' => 'boolean',
            'randomize_options' => 'boolean',
            'allow_back_navigation' => 'boolean',
            'show_result_after_submission' => 'boolean',
            'max_attempts' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
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
     * @return BelongsTo<ClassSection, $this>
     */
    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    /**
     * @return HasMany<ExamQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    protected static function newFactory(): ExamFactory
    {
        return ExamFactory::new();
    }
}
