<?php

namespace Modules\ApprovalWorkflow\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ApprovalWorkflow\Enums\ApprovalActionType;

/**
 * @property string $id
 * @property string $approval_request_step_id
 * @property string $acted_by
 * @property ApprovalActionType $action
 * @property string|null $comment
 * @property string|null $delegated_to
 * @property string|null $ip_address
 * @property CarbonImmutable $created_at
 * @property-read User|null $actedBy
 * @property-read User|null $delegatedTo
 * @property-read ApprovalRequestStep $step
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction whereActedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction whereApprovalRequestStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction whereDelegatedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalAction whereIpAddress($value)
 *
 * @mixin \Eloquent
 */
class ApprovalAction extends Model
{
    use HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'approval_request_step_id', 'acted_by', 'action', 'comment', 'delegated_to', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'action' => ApprovalActionType::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ApprovalRequestStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequestStep::class, 'approval_request_step_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }
}
