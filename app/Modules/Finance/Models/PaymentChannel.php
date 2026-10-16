<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Enums\ChannelCategory;
use Modules\Finance\Enums\ChannelStatus;

/** Registri kanal pembayaran per tenant; kanal online dibatasi kuota paket (§14.3). */
class PaymentChannel extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'category', 'provider', 'method', 'bank_code', 'fee_amount', 'fee_bearer', 'expiry_minutes', 'instructions', 'status', 'counts_toward_quota', 'environment', 'sort_order', 'created_by'];

    protected function casts(): array
    {
        return [
            'category' => ChannelCategory::class,
            'status' => ChannelStatus::class,
            'fee_amount' => 'decimal:2',
            'expiry_minutes' => 'integer',
            'counts_toward_quota' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
