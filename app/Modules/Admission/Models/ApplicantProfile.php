<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $applicant_account_id
 * @property string|null $full_name
 * @property string|null $nationality
 * @property string|null $nik
 * @property string|null $passport_number
 * @property string|null $passport_country
 * @property string|null $identity_hash
 * @property string|null $birth_place
 * @property CarbonImmutable|null $birth_date
 * @property string|null $gender
 * @property string|null $religion
 * @property string|null $mother_name
 * @property string|null $address_line
 * @property string|null $rt
 * @property string|null $rw
 * @property string|null $village
 * @property string|null $district
 * @property string|null $city
 * @property string|null $province
 * @property string|null $region_code
 * @property string|null $postal_code
 * @property array<mixed>|null $special_needs
 * @property string|null $special_needs_note
 * @property array<mixed>|null $pddikti_extra
 * @property int $lock_version
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ApplicantAccount $account
 */
class ApplicantProfile extends PmbModel
{
    protected $table = 'pmb_applicant_profiles';

    /** @var array<int, string> */
    protected $hidden = ['nik', 'passport_number', 'mother_name', 'special_needs', 'special_needs_note', 'identity_hash'];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'passport_number' => 'encrypted',
            'mother_name' => 'encrypted',
            'special_needs' => 'encrypted:array',
            'special_needs_note' => 'encrypted',
            'pddikti_extra' => 'encrypted:array',
            'birth_date' => 'date',
            'lock_version' => 'integer',
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
