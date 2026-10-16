<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Skema cicilan (FR-CCL-01). */
class InstallmentPlan extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'bill_groups', 'terms', 'min_first_payment', 'auto_approve', 'require_no_arrears', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'bill_groups' => 'array',
            'terms' => 'array',
            'min_first_payment' => 'decimal:2',
            'auto_approve' => 'boolean',
            'require_no_arrears' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
