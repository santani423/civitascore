<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $applicant_account_id
 * @property string $relationship
 * @property string $full_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $nik
 * @property CarbonImmutable|null $birth_date
 * @property string|null $education_level
 * @property string|null $occupation
 * @property string|null $income_range
 * @property bool $is_deceased
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ApplicantAccount $account
 */
class ApplicantGuardian extends PmbModel
{
    protected $table = 'pmb_applicant_guardians';

    /** @var array<int, string> */
    protected $hidden = ['nik'];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'birth_date' => 'date',
            'is_deceased' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ApplicantAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ApplicantAccount::class, 'applicant_account_id');
    }
}
