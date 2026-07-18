<?php

namespace Modules\ApprovalWorkflow\Tests\Fixtures;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @internal Test-only fixture — see the migration in Tests/Fixtures/Migrations
 * for why this exists instead of a real business approvable.
 *
 * @property-read User|null $createdBy
 *
 * @method static \Modules\ApprovalWorkflow\Tests\Fixtures\ApprovalDemoItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDemoItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDemoItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalDemoItem query()
 *
 * @mixin \Eloquent
 */
class ApprovalDemoItem extends Model
{
    /** @use HasFactory<ApprovalDemoItemFactory> */
    use HasFactory, HasUlids;

    protected $table = 'approval_demo_items';

    protected $fillable = ['title', 'created_by'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): ApprovalDemoItemFactory
    {
        return ApprovalDemoItemFactory::new();
    }
}
