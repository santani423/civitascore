<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Enums\AidCalcType;
use Modules\Finance\Enums\VoucherStatus;

/** Voucher potongan tagihan (FR-VCR-01). */
class Voucher extends Model implements ScopesToInstitution
{
    use HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'discount_type', 'discount_value', 'max_discount', 'bill_groups', 'scope', 'quota_total', 'quota_used', 'per_student_limit', 'min_invoice_amount', 'valid_from', 'valid_until', 'status', 'created_by'];

    protected function casts(): array
    {
        return [
            'discount_type' => AidCalcType::class,
            'discount_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'bill_groups' => 'array',
            'scope' => 'array',
            'quota_total' => 'integer',
            'quota_used' => 'integer',
            'per_student_limit' => 'integer',
            'min_invoice_amount' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'status' => VoucherStatus::class,
        ];
    }

    /** @return HasMany<VoucherRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }
}
