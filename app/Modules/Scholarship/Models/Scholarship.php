<?php

namespace Modules\Scholarship\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Scholarship\Database\Factories\ScholarshipFactory;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $name
 * @property string $provider
 * @property int $quota
 * @property string $amount
 * @property string $academic_year
 * @property CarbonImmutable $registration_start
 * @property CarbonImmutable $registration_end
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read University $university
 */
class Scholarship extends Model implements ScopesToInstitution
{
    /** @use HasFactory<ScholarshipFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'name', 'provider', 'quota', 'amount',
        'academic_year', 'registration_start', 'registration_end', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'amount' => 'decimal:2',
            'registration_start' => 'date',
            'registration_end' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return HasMany<ScholarshipApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }

    protected static function newFactory(): ScholarshipFactory
    {
        return ScholarshipFactory::new();
    }
}
