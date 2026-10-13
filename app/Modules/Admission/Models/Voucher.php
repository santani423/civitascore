<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admission\Enums\InvoiceType;

class Voucher extends PmbModel
{
    use SoftDeletes;

    protected $table = 'pmb_vouchers';

    protected function casts(): array
    {
        return [
            'applies_to' => InvoiceType::class,
            'discount_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'scope' => 'array',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
