<?php

namespace Modules\Tenancy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportSession extends Model
{
    use HasUlids;

    protected $fillable = [
        'super_admin_id', 'university_id', 'reason', 'started_at', 'ended_at',
        'ip_address', 'user_agent', 'actions_performed', 'approval_reference',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'actions_performed' => 'array',
        ];
    }

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_admin_id');
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}
