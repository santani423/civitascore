<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\AssessmentReviewStatus;
use Modules\Admission\Enums\AssessmentSessionStatus;

/**
 * Sesi asesmen satu pendaftaran pada satu versi (unik per pasangan itu).
 * `item_order` dibekukan saat mulai, sama seperti urutan soal CBT.
 *
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property string $version_id
 * @property AssessmentSessionStatus $status
 * @property array<int, string> $item_order
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $deadline_at
 * @property CarbonImmutable|null $submitted_at
 * @property string|null $holland_code
 * @property array<int, string>|null $dominant_dimensions
 * @property string|null $scoring_engine
 * @property CarbonImmutable|null $scored_at
 * @property CarbonImmutable|null $published_at
 * @property AssessmentReviewStatus $review_status
 * @property string|null $invalidated_by
 * @property string $mode
 * @property CarbonImmutable|null $invalidated_at
 * @property string|null $invalidation_reason
 * @property-read Application $application
 * @property-read AssessmentVersion $version
 * @property-read Collection<int, AssessmentResponse> $responses
 * @property-read Collection<int, AssessmentScore> $scores
 * @property-read Collection<int, AssessmentSessionEvent> $events
 */
class AssessmentSession extends PmbModel
{
    protected $table = 'pmb_assessment_sessions';

    protected function casts(): array
    {
        return [
            'status' => AssessmentSessionStatus::class,
            'item_order' => 'array',
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'dominant_dimensions' => 'array',
            'scored_at' => 'datetime',
            'published_at' => 'datetime',
            'review_status' => AssessmentReviewStatus::class,
            'invalidated_at' => 'datetime',
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
     * @return BelongsTo<AssessmentVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'version_id');
    }

    /**
     * @return HasMany<AssessmentResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(AssessmentResponse::class, 'session_id');
    }

    /**
     * @return HasMany<AssessmentScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class, 'session_id')->orderBy('rank');
    }

    /**
     * @return HasMany<AssessmentReviewNote, $this>
     */
    public function reviewNotes(): HasMany
    {
        return $this->hasMany(AssessmentReviewNote::class, 'session_id')->orderBy('created_at');
    }

    /**
     * @return HasMany<AssessmentSessionEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(AssessmentSessionEvent::class, 'session_id');
    }
}
