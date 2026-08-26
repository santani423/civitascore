<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\Factories\ExamAttemptAnswerFactory;

/**
 * @property string $id
 * @property string $university_id
 * @property string $exam_attempt_id
 * @property string $exam_question_id
 * @property string|null $exam_question_option_id
 * @property CarbonImmutable $answered_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExamAttempt $examAttempt
 * @property-read ExamQuestion $examQuestion
 * @property-read ExamQuestionOption|null $examQuestionOption
 */
class ExamAttemptAnswer extends Model implements ScopesToInstitution
{
    /** @use HasFactory<ExamAttemptAnswerFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'exam_attempt_id', 'exam_question_id', 'exam_question_option_id', 'answered_at'];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<ExamAttempt, $this>
     */
    public function examAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    /**
     * @return BelongsTo<ExamQuestion, $this>
     */
    public function examQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class);
    }

    /**
     * @return BelongsTo<ExamQuestionOption, $this>
     */
    public function examQuestionOption(): BelongsTo
    {
        return $this->belongsTo(ExamQuestionOption::class);
    }

    protected static function newFactory(): ExamAttemptAnswerFactory
    {
        return ExamAttemptAnswerFactory::new();
    }
}
