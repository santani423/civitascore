<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\RunStatus;
use Modules\ApprovalWorkflow\Contracts\RequiresDistinctApprover;

/**
 * @property string $id
 * @property string $university_id
 * @property string $wave_path_id
 * @property string $selection_scheme_id
 * @property string $run_type
 * @property array<mixed>|null $scope_application_ids
 * @property RunStatus $status
 * @property array<mixed>|null $quota_snapshot
 * @property array<mixed>|null $summary
 * @property string|null $result_hash
 * @property bool $has_unresolved_ties
 * @property CarbonImmutable|null $computed_at
 * @property string|null $approval_request_id
 * @property string|null $submitted_by
 * @property CarbonImmutable|null $approved_at
 * @property string|null $review_note
 * @property string|null $superseded_by_id
 * @property string|null $correction_request_id
 * @property string|null $failure_reason
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read WavePath $wavePath
 * @property-read SelectionScheme $scheme
 * @property-read Collection<int, SelectionRunResult> $results
 * @property-read User|null $creator
 * @property-read User|null $submitter
 * @property-read Collection<int, AnnouncementBatch> $batches
 */
class SelectionRun extends PmbModel implements RequiresDistinctApprover
{
    protected $table = 'pmb_selection_runs';

    protected function casts(): array
    {
        return [
            'status' => RunStatus::class,
            'scope_application_ids' => 'array',
            'quota_snapshot' => 'array',
            'summary' => 'array',
            'has_unresolved_ties' => 'boolean',
            'computed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WavePath, $this>
     */
    public function wavePath(): BelongsTo
    {
        return $this->belongsTo(WavePath::class, 'wave_path_id');
    }

    /**
     * @return BelongsTo<SelectionScheme, $this>
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(SelectionScheme::class, 'selection_scheme_id');
    }

    /**
     * @return HasMany<SelectionRunResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(SelectionRunResult::class, 'selection_run_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return HasMany<AnnouncementBatch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(AnnouncementBatch::class, 'selection_run_id');
    }

    /** BR-30: pengaju (submitted_by) dikecualikan; pembuat run juga tidak mengesahkan run-nya. */
    public function excludedApproverIds(): array
    {
        return array_values(array_filter([(string) $this->submitted_by, (string) $this->created_by]));
    }
}
