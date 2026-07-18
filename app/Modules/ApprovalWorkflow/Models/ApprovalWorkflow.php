<?php

namespace Modules\ApprovalWorkflow\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ApprovalWorkflow\Database\Factories\ApprovalWorkflowFactory;

/**
 * @property string $id
 * @property string $name
 * @property string $workflowable_type
 * @property array<array-key, mixed>|null $conditions
 * @property bool $is_active
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Collection<int, ApprovalWorkflowStep> $steps
 * @property-read int|null $steps_count
 *
 * @method static \Modules\ApprovalWorkflow\Database\Factories\ApprovalWorkflowFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereWorkflowableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow withoutTrashed()
 *
 * @mixin \Eloquent
 */
class ApprovalWorkflow extends Model
{
    /** @use HasFactory<ApprovalWorkflowFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = ['name', 'workflowable_type', 'conditions', 'is_active', 'description'];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ApprovalWorkflowStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStep::class)->orderBy('sequence');
    }

    protected static function newFactory(): ApprovalWorkflowFactory
    {
        return ApprovalWorkflowFactory::new();
    }
}
