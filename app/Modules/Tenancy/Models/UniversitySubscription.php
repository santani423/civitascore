<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Database\Factories\UniversitySubscriptionFactory;
use Modules\Tenancy\Enums\SubscriptionStatus;

class UniversitySubscription extends Model
{
    /** @use HasFactory<UniversitySubscriptionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'university_id', 'subscription_plan_id', 'status',
        'trial_ends_at', 'current_period_starts_at', 'current_period_ends_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    protected static function newFactory(): UniversitySubscriptionFactory
    {
        return UniversitySubscriptionFactory::new();
    }
}
