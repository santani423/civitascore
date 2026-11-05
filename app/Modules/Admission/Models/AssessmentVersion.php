<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\AssessmentPurpose;
use Modules\Admission\Enums\AssessmentVersionStatus;
use Modules\ApprovalWorkflow\Contracts\RequiresDistinctApprover;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;

/**
 * Versi instrumen. Selama `draft` dimensi dan butir boleh diubah; sejak diajukan isinya
 * dibekukan (docs/one-atma-talent/05). Penyusun tidak boleh memutuskan persetujuannya sendiri.
 *
 * @property string $id
 * @property string $university_id
 * @property string $assessment_type_id
 * @property string|null $period_id
 * @property string|null $parent_version_id
 * @property string|null $approval_request_id
 * @property int $version_no
 * @property AssessmentPurpose $purpose
 * @property AssessmentVersionStatus $status
 * @property string $title
 * @property string|null $instructions
 * @property string|null $change_note
 * @property int|null $duration_minutes
 * @property CarbonImmutable|null $available_from
 * @property CarbonImmutable|null $available_until
 * @property array<string, mixed> $scoring_config
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $archived_at
 * @property string|null $created_by
 * @property string|null $submitted_by
 * @property-read AssessmentType $type
 * @property-read ApprovalRequest|null $approvalRequest
 * @property-read Collection<int, AssessmentDimension> $dimensions
 * @property-read Collection<int, AssessmentItem> $items
 * @property-read Collection<int, AssessmentSession> $sessions
 */
class AssessmentVersion extends PmbModel implements RequiresDistinctApprover
{
    protected $table = 'pmb_assessment_versions';

    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
            'duration_minutes' => 'integer',
            'purpose' => AssessmentPurpose::class,
            'status' => AssessmentVersionStatus::class,
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'scoring_config' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /** Pemohon dan penyusun tidak boleh memutuskan pengajuan versinya (ApprovalWorkflow, BR-20). */
    public function excludedApproverIds(): array
    {
        return array_values(array_filter([$this->created_by, $this->submitted_by], fn ($id) => $id !== null));
    }

    /**
     * @return BelongsTo<AssessmentType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class, 'assessment_type_id');
    }

    /**
     * @return BelongsTo<AssessmentVersion, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_version_id');
    }

    /**
     * @return BelongsTo<ApprovalRequest, $this>
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * @return BelongsTo<Period, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    /**
     * @return HasMany<AssessmentDimension, $this>
     */
    public function dimensions(): HasMany
    {
        return $this->hasMany(AssessmentDimension::class, 'version_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<AssessmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AssessmentItem::class, 'version_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<AssessmentSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(AssessmentSession::class, 'version_id');
    }
}
