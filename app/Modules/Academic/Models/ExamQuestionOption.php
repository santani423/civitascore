<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\Factories\ExamQuestionOptionFactory;

/**
 * @property string $id
 * @property string $university_id
 * @property string $exam_question_id
 * @property string $option_text
 * @property bool $is_correct
 * @property int $order_index
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExamQuestion $examQuestion
 */
class ExamQuestionOption extends Model implements ScopesToInstitution
{
    /** @use HasFactory<ExamQuestionOptionFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'exam_question_id', 'option_text', 'is_correct', 'order_index'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'order_index' => 'integer'];
    }

    /**
     * @return BelongsTo<ExamQuestion, $this>
     */
    public function examQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class);
    }

    protected static function newFactory(): ExamQuestionOptionFactory
    {
        return ExamQuestionOptionFactory::new();
    }
}
