<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Kunci idempotensi request (11 §A.1). `scope` sudah memuat tenant & aktor. */
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
