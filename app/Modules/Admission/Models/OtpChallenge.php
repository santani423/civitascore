<?php

namespace Modules\Admission\Models;

class OtpChallenge extends PmbModel
{
    protected $table = 'pmb_otp_challenges';

    /** @var array<int, string> */
    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
