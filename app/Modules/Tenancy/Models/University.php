<?php

namespace Modules\Tenancy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenancy\Database\Factories\UniversityFactory;
use Modules\Tenancy\Enums\UniversityStatus;

/**
 * @property string $id
 * @property string $code
 * @property string $slug
 * @property string $name
 * @property UniversityStatus $status
 * @property bool $is_active
 */
class University extends Model
{
    /** @use HasFactory<UniversityFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'code', 'slug', 'name', 'short_name', 'legal_name',
        'education_institution_type', 'accreditation',
        'email', 'phone', 'website',
        'status', 'timezone', 'locale', 'currency', 'date_format',
        'logo_file_id', 'primary_color', 'secondary_color',
        'is_active', 'activated_at', 'suspended_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => UniversityStatus::class,
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(UniversityDomain::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(UniversitySetting::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(UniversityModule::class);
    }

    public function featureFlags(): HasMany
    {
        return $this->hasMany(UniversityFeatureFlag::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UniversitySubscription::class);
    }

    public function currentSubscription(): HasMany
    {
        return $this->subscriptions()->latest('created_at');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UserUniversity::class);
    }

    protected static function newFactory(): UniversityFactory
    {
        return UniversityFactory::new();
    }
}
