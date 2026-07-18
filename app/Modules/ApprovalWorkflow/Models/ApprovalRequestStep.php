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
use Modules\ApprovalWorkflow\Database\Factories\ApprovalRequestStepFactory;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStepStatus;

/**
 * @property string $id
 * @property string $approval_request_id
 * @property string $approval_workflow_step_id
 * @property int $sequence
 * @property string|null $assigned_approver_user_id
 * @property ApprovalRequestStepStatus $status
 * @property CarbonImmutable|null $acted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, ApprovalAction> $actions
 * @property-read int|null $actions_count
 * @property-read User|null $assignedApprover
 * @property-read ApprovalRequest|null $request
 * @property-read ApprovalWorkflowStep $workflowStep
 *
 * @method static \Modules\ApprovalWorkflow\Database\Factories\ApprovalRequestStepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereActedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereApprovalRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereApprovalWorkflowStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereAssignedApproverUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequestStep whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApprovalRequestStep extends Model
{
    /** @use HasFactory<ApprovalRequestStepFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'approval_request_id', 'approval_workflow_step_id', 'sequence',
        'assigned_approver_user_id', 'status', 'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalRequestStepStatus::class,
            'acted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ApprovalRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * @return BelongsTo<ApprovalWorkflowStep, $this>
     */
    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflowStep::class, 'approval_workflow_step_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_approver_user_id');
    }

    /**
     * @return HasMany<ApprovalAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class)->orderBy('created_at');
    }

    protected static function newFactory(): ApprovalRequestStepFactory
    {
        return ApprovalRequestStepFactory::new();
    }
}
