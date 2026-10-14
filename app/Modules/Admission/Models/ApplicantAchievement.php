<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $applicant_account_id
 * @property string $name
 * @property string $category
 * @property string $level
 * @property string $rank
 * @property int $year
 * @property string|null $organizer
 * @property string|null $application_document_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ApplicantAccount $account
 * @property-read ApplicationDocument|null $document
 */
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
