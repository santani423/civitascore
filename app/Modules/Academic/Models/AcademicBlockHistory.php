<?php

namespace Modules\Academic\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Riwayat cekal append-only (A.8.5). actor_id null = sistem. */
class AcademicBlockHistory extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'academic_block_id', 'action', 'from_status', 'to_status', 'actor_id', 'reason', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
