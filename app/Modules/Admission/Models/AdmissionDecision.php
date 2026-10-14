<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Academic\Models\StudyProgram;
use Modules\Admission\Enums\Decision;
use Modules\Admission\Enums\DecisionSource;
use Modules\Admission\Enums\PublicationStatus;

/**
 * Keputusan penerimaan berversi (SM-09): setiap koreksi/promosi = baris baru,
 * hanya satu `is_current` per aplikasi (unique `current_key`).
 *
 *
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property int $decision_version
 * @property bool $is_current
 * @property Decision $decision
 * @property DecisionSource $source
 * @property string|null $selection_run_id
 * @property string|null $run_result_id
 * @property string|null $quota_id
 * @property string|null $study_program_id
 * @property string|null $study_system_id
 * @property int|null $waitlist_rank
 * @property array<mixed>|null $waitlist_entries
 * @property PublicationStatus $publication_status
 * @property CarbonImmutable|null $published_at
 * @property string|null $announcement_batch_id
 * @property string|null $correction_note
 * @property array<mixed>|null $scholarship_award
 * @property string|null $previous_decision_id
 * @property string|null $current_key
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Application $application
 * @property-read Quota|null $quota
 * @property-read StudyProgram|null $studyProgram
 * @property-read StudySystem|null $studySystem
 * @property-read SelectionRun|null $run
 * @property-read AcceptanceLetter|null $letter
 */
class AdmissionDecision extends PmbModel
{
    protected $table = 'pmb_admission_decisions';

    protected static function booted(): void
    {
        static::saving(function (self $decision): void {
            $decision->current_key = $decision->is_current ? $decision->application_id : null;
        });
    }

    protected function casts(): array
    {
        return [
            'decision' => Decision::class,
            'source' => DecisionSource::class,
            'publication_status' => PublicationStatus::class,
            'decision_version' => 'integer',
            'is_current' => 'boolean',
            'waitlist_rank' => 'integer',
            'waitlist_entries' => 'array',
            'scholarship_award' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Keputusan yang boleh dilihat pendaftar/publik (J.3 butir 1).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status', PublicationStatus::Published->value);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return BelongsTo<Quota, $this>
     */
    public function quota(): BelongsTo
    {
        return $this->belongsTo(Quota::class, 'quota_id');
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    /**
     * @return BelongsTo<StudySystem, $this>
     */
    public function studySystem(): BelongsTo
    {
        return $this->belongsTo(StudySystem::class, 'study_system_id');
    }

    /**
     * @return BelongsTo<SelectionRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(SelectionRun::class, 'selection_run_id');
    }

    /**
     * @return HasOne<AcceptanceLetter, $this>
     */
    public function letter(): HasOne
    {
        return $this->hasOne(AcceptanceLetter::class, 'decision_id');
    }
}
