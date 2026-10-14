<?php

namespace Modules\Admission\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Models\University;

/**
 * Basis seluruh model `pmb_*`: ULID, ter-scope tenant (TenantScoped), dan
 * `university_id` diisi otomatis dari TenantContext.
 *
 * Mass assignment: hanya `id` yang dijaga. Kolom status & kolom turunan
 * tidak pernah diisi dari input klien — service domain selalu membangun
 * array atribut secara eksplisit dari data tervalidasi (13 §B.2).
 */
abstract class PmbModel extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}
