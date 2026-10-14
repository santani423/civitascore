<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admission\Enums\InvoiceType;

/**
 * @property string $id
 * @property string $university_id
 * @property string $code
 * @property string $name
 * @property InvoiceType $applies_to
 * @property string $discount_type
 * @property string $discount_value
 * @property string|null $max_discount
 * @property array<mixed>|null $scope
 * @property int|null $max_uses
 * @property int $used_count
 * @property CarbonImmutable $valid_from
 * @property CarbonImmutable $valid_until
 * @property bool $is_active
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
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
