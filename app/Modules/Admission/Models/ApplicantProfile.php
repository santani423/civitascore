<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
