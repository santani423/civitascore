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
use Modules\Academic\Database\Factories\ExamAttemptFactory;
use Modules\Academic\Enums\ExamAttemptStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $exam_id
 * @property string $krs_item_id
 * @property int $attempt_number
 * @property ExamAttemptStatus $status
 * @property array<int, string> $question_order
 * @property array<string, array<int, string>> $option_order
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $submitted_at
 * @property string|null $score
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Exam $exam
 * @property-read KrsItem $krsItem
 * @property-read Collection<int, ExamAttemptAnswer> $answers
 */
class ExamAttempt extends Model implements ScopesToInstitution
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'exam_id', 'krs_item_id', 'attempt_number', 'status',
        'question_order', 'option_order', 'started_at', 'submitted_at', 'score',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'status' => ExamAttemptStatus::class,
            'question_order' => 'array',
            'option_order' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return BelongsTo<KrsItem, $this>
     */
    public function krsItem(): BelongsTo
    {
        return $this->belongsTo(KrsItem::class);
    }

    /**
     * @return HasMany<ExamAttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class);
    }

    protected static function newFactory(): ExamAttemptFactory
    {
        return ExamAttemptFactory::new();
    }
}
