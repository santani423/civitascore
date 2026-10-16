<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Enums\FeeCategory;

/** Master komponen biaya (FR-CFG-01). */
class FeeComponent extends Model implements ScopesToInstitution
{
    use HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'category', 'billing_frequency', 'is_mandatory', 'installable', 'discountable', 'refundable', 'counts_for_clearance', 'pddikti_category', 'gl_account_code', 'description', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'category' => FeeCategory::class,
            'is_mandatory' => 'boolean',
            'installable' => 'boolean',
            'discountable' => 'boolean',
            'refundable' => 'boolean',
            'counts_for_clearance' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
