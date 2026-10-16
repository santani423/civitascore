<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Enums\AidCalcType;
use Modules\Finance\Enums\AidKind;
use Modules\Scholarship\Models\Scholarship;

/** Master jenis beasiswa/potongan/subsidi/KIP (FR-BSW-02). */
class DiscountPolicy extends Model implements ScopesToInstitution
{
    use HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'kind', 'funder_name', 'scholarship_id', 'calc_type', 'value', 'max_amount', 'fee_component_ids', 'priority', 'stackable', 'description', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'kind' => AidKind::class,
            'calc_type' => AidCalcType::class,
            'value' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'fee_component_ids' => 'array',
            'priority' => 'integer',
            'stackable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Scholarship, $this> */
    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    /** @return HasMany<FinancialAidAward, $this> */
    public function awards(): HasMany
    {
        return $this->hasMany(FinancialAidAward::class);
    }
}
