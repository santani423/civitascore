<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $university_id
 * @property string $applicant_account_id
 * @property string $level
 * @property string $institution_name
 * @property string|null $institution_npsn
 * @property string|null $school_type
 * @property string|null $major
 * @property int|null $graduation_year
 * @property bool $is_graduating
 * @property string|null $nisn
 * @property string|null $previous_student_id
 * @property string|null $gpa
 * @property int|null $credits_earned
 * @property string|null $previous_accreditation
 * @property string|null $city
 * @property string|null $province
 * @property string|null $country
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ApplicantAccount $account
 * @property-read Collection<int, ReportScore> $reportScores
 */
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
