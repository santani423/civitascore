<?php

namespace Modules\Admission\Models;

use Modules\ApprovalWorkflow\Contracts\RequiresDistinctApprover;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\RunStatus;

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
