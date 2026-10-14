<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $applicant_education_id
 * @property int $semester
 * @property string $subject_code
 * @property string $subject_name
 * @property string $score
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ApplicantEducation $education
 */
class ReportScore extends PmbModel
{
    protected $table = 'pmb_report_scores';

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'score' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ApplicantEducation, $this>
     */
    public function education(): BelongsTo
    {
        return $this->belongsTo(ApplicantEducation::class, 'applicant_education_id');
    }
}
