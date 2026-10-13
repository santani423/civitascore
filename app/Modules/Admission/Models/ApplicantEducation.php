<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicantEducation extends PmbModel
{
    protected $table = 'pmb_applicant_educations';

    protected function casts(): array
    {
        return [
            'gpa' => 'decimal:2',
            'graduation_year' => 'integer',
            'credits_earned' => 'integer',
            'is_graduating' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ApplicantAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ApplicantAccount::class, 'applicant_account_id');
    }

    /**
     * @return HasMany<ReportScore, $this>
     */
    public function reportScores(): HasMany
    {
        return $this->hasMany(ReportScore::class, 'applicant_education_id');
    }
}
