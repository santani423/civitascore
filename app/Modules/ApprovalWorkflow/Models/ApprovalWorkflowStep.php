<?php

namespace Modules\ApprovalWorkflow\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ApprovalWorkflow\Database\Factories\ApprovalWorkflowStepFactory;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Enums\ApprovalRejectAction;
use Modules\UserManagement\Models\Role;

/**
 * @property string $id
 * @property string $approval_workflow_id
 * @property int $sequence
 * @property string $name
 * @property ApprovalApproverType $approver_type
 * @property string|null $approver_role_id
 * @property string|null $approver_user_id
 * @property ApprovalRejectAction $action_on_reject
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Role|null $approverRole
 * @property-read User|null $approverUser
 * @property-read ApprovalWorkflow|null $workflow
 *
 * @method static \Modules\ApprovalWorkflow\Database\Factories\ApprovalWorkflowStepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereActionOnReject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereApprovalWorkflowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereApproverRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereApproverType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereApproverUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflowStep whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApprovalWorkflowStep extends Model
{
    /** @use HasFactory<ApprovalWorkflowStepFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'approval_workflow_id', 'sequence', 'name', 'approver_type',
        'approver_role_id', 'approver_user_id', 'action_on_reject',
    ];

    protected function casts(): array
    {
        return [
            'approver_type' => ApprovalApproverType::class,
            'action_on_reject' => ApprovalRejectAction::class,
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
     * @return BelongsTo<Role, $this>
     */
    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    protected static function newFactory(): ApprovalWorkflowStepFactory
    {
        return ApprovalWorkflowStepFactory::new();
    }
}
