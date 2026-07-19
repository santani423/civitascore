<?php

namespace Modules\Tenancy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Database\Factories\UserUniversityFactory;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;

class UserUniversity extends Model
{
    /** @use HasFactory<UserUniversityFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id', 'university_id', 'membership_type', 'status', 'joined_at', 'left_at', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'membership_type' => MembershipType::class,
            'status' => MembershipStatus::class,
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    protected static function newFactory(): UserUniversityFactory
    {
        return UserUniversityFactory::new();
    }
}
