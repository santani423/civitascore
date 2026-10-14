<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\ScoreSource;

/** Nilai komponen seleksi per aplikasi (per penguji untuk komponen multi-penguji).
 *
 *
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property string $selection_component_id
 * @property string|null $examiner_user_id
 * @property string|null $raw_score
 * @property bool|null $passed
 * @property array<mixed>|null $rubric_scores
 * @property string|null $note
 * @property ScoreSource $source
 * @property string|null $exam_session_id
 * @property CarbonImmutable|null $locked_at
 * @property string|null $locked_by_run_id
 * @property string|null $entered_by
 * @property string $unique_key
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Application $application
 * @property-read SelectionComponent $component
 * @property-read User|null $examiner
 * @property-read Collection<int, ScoreRevision> $revisions
 */
class SelectionScore extends PmbModel
{
    protected $table = 'pmb_selection_scores';

    protected static function booted(): void
    {
        static::saving(function (self $score): void {
            $score->unique_key = $score->application_id.':'.$score->selection_component_id.':'.($score->examiner_user_id ?? '-');
        });
    }

    protected function casts(): array
    {
        return [
            'raw_score' => 'decimal:4',
            'passed' => 'boolean',
            'rubric_scores' => 'array',
            'source' => ScoreSource::class,
            'locked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return BelongsTo<SelectionComponent, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(SelectionComponent::class, 'selection_component_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_user_id');
    }

    /**
     * @return HasMany<ScoreRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(ScoreRevision::class, 'selection_score_id')->orderBy('created_at');
    }
}
