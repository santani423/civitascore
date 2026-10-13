<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantAchievement extends PmbModel
{
    protected $table = 'pmb_applicant_achievements';

    protected function casts(): array
    {
        return [
            'year' => 'integer',
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
     * @return BelongsTo<ApplicationDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }
}
