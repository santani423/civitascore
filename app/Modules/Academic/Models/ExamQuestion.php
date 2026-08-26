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
use Modules\Academic\Database\Factories\ExamQuestionFactory;

/**
 * @property string $id
 * @property string $university_id
 * @property string $exam_id
 * @property string|null $question_bank_item_id
 * @property string $question_text
 * @property string $points
 * @property int $order_index
 * @property bool $is_selected
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Exam $exam
 * @property-read QuestionBankItem|null $questionBankItem
 * @property-read Collection<int, ExamQuestionOption> $options
 */
class ExamQuestion extends Model implements ScopesToInstitution
{
    /** @use HasFactory<ExamQuestionFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'exam_id', 'question_bank_item_id', 'question_text', 'points', 'order_index', 'is_selected',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'order_index' => 'integer',
            'is_selected' => 'boolean',
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
     * @return HasMany<ExamQuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ExamQuestionOption::class);
    }

    /**
     * @return BelongsTo<QuestionBankItem, $this>
     */
    public function questionBankItem(): BelongsTo
    {
        return $this->belongsTo(QuestionBankItem::class);
    }

    protected static function newFactory(): ExamQuestionFactory
    {
        return ExamQuestionFactory::new();
    }
}
