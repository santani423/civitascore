<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
