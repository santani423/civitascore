<?php

namespace Modules\ApprovalWorkflow\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ApprovalWorkflow\Enums\ApprovalHistoryEvent;

/**
 * @property string $id
 * @property string $approval_request_id
 * @property string|null $approval_request_step_id
 * @property ApprovalHistoryEvent $event
 * @property string|null $actor_id
 * @property string $description
 * @property array<array-key, mixed>|null $metadata
 * @property CarbonImmutable $created_at
 * @property-read User|null $actor
 * @property-read ApprovalRequest|null $request
 * @property-read ApprovalRequestStep|null $step
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory whereApprovalRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory whereApprovalRequestStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalHistory whereMetadata($value)
 *
 * @mixin \Eloquent
 */
class ApprovalHistory extends Model
{
    use HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'approval_request_id', 'approval_request_step_id', 'event', 'actor_id', 'description', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event' => ApprovalHistoryEvent::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
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
     * @return BelongsTo<ApprovalRequestStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequestStep::class, 'approval_request_step_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
