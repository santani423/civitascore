<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\AcademicTerm;
use Modules\Finance\Enums\RateTableStatus;

/** Tabel tarif berversi; published tidak dapat diubah (BR-KEU-01). */
class FeeRateTable extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'academic_year', 'effective_term_id', 'version', 'status', 'notes', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'rejection_reason', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'status' => RateTableStatus::class,
            'version' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /** @return HasMany<FeeRate, $this> */
    public function rates(): HasMany
    {
        return $this->hasMany(FeeRate::class);
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function effectiveTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'effective_term_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
