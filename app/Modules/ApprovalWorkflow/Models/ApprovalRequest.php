<?php

namespace Modules\ApprovalWorkflow\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ApprovalWorkflow\Database\Factories\ApprovalRequestFactory;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;

/**
 * @property string $id
 * @property string $approval_workflow_id
 * @property string $requestable_type
 * @property string $requestable_id
 * @property string $requested_by
 * @property string|null $current_step_id
 * @property ApprovalRequestStatus $status
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $completed_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read ApprovalRequestStep|null $currentStep
 * @property-read Collection<int, ApprovalHistory> $histories
 * @property-read int|null $histories_count
 * @property-read Model $requestable
 * @property-read User|null $requestedBy
 * @property-read Collection<int, ApprovalRequestStep> $steps
 * @property-read int|null $steps_count
 * @property-read ApprovalWorkflow|null $workflow
 *
 * @method static \Modules\ApprovalWorkflow\Database\Factories\ApprovalRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereApprovalWorkflowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCurrentStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereRequestableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereRequestableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest withoutTrashed()
 *
 * @mixin \Eloquent
 */
class ApprovalRequest extends Model
{
    /** @use HasFactory<ApprovalRequestFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'approval_workflow_id', 'requestable_type', 'requestable_id', 'requested_by',
        'current_step_id', 'status', 'submitted_at', 'completed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalRequestStatus::class,
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ApprovalWorkflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<ApprovalRequestStep, $this>
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequestStep::class, 'current_step_id');
    }

    /**
     * @return HasMany<ApprovalRequestStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalRequestStep::class)->orderBy('sequence');
    }

    /**
     * @return HasMany<ApprovalHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class)->orderBy('created_at');
    }

    protected static function newFactory(): ApprovalRequestFactory
    {
        return ApprovalRequestFactory::new();
    }
}
