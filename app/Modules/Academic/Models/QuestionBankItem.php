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
use Modules\Academic\Database\Factories\QuestionBankItemFactory;

/**
 * Soal pilihan ganda yang dapat dipakai ulang lintas ujian/semester,
 * independen dari satu ujian tertentu (spec §7 "Question Bank"). Menerapkan
 * item bank ke sebuah ujian (QuestionBankService::applyToExam()) membuat
 * SALINAN sebagai ExamQuestion/ExamQuestionOption baru — mengubah item bank
 * setelahnya tidak memengaruhi ujian yang sudah menerapkannya.
 *
 * @property string $id
 * @property string $university_id
 * @property string|null $course_id
 * @property string $question_text
 * @property string $points
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Course|null $course
 * @property-read Collection<int, QuestionBankItemOption> $options
 * @property-read Collection<int, ExamQuestion> $examQuestions
 */
class QuestionBankItem extends Model implements ScopesToInstitution
{
    /** @use HasFactory<QuestionBankItemFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'course_id', 'question_text', 'points'];

    protected function casts(): array
    {
        return ['points' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<QuestionBankItemOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionBankItemOption::class);
    }

    /**
     * @return HasMany<ExamQuestion, $this>
     */
    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    protected static function newFactory(): QuestionBankItemFactory
    {
        return QuestionBankItemFactory::new();
    }
}
