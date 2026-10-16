<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Golongan UKT (FR-UKT-01). */
class UktGroup extends Model implements ScopesToInstitution
{
    use HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'level', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<StudentUktAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(StudentUktAssignment::class);
    }
}
