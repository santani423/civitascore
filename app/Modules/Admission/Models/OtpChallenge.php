<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;

/**
 * @property string $id
 * @property string $university_id
 * @property string|null $applicant_account_id
 * @property string $channel
 * @property string $destination_hash
 * @property string $purpose
 * @property string $code_hash
 * @property array<mixed>|null $payload
 * @property CarbonImmutable $expires_at
 * @property int $attempts
 * @property CarbonImmutable|null $consumed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
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
