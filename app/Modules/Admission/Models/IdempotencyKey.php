<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Kunci idempotensi request (11 §A.1). `scope` sudah memuat tenant & aktor.
 *
 *
 * @property string $id
 * @property string $scope
 * @property string $key
 * @property string $request_hash
 * @property int|null $response_status
 * @property string|null $response_body
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class IdempotencyKey extends Model
{
    use HasUlids;

    protected $table = 'pmb_idempotency_keys';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'response_status' => 'integer',
        ];
    }
}
