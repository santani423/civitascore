<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $applicant_account_id
 * @property string|null $application_id
 * @property string $consent_type
 * @property string|null $content_id
 * @property int|null $document_version
 * @property CarbonImmutable $granted_at
 * @property CarbonImmutable|null $revoked_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonImmutable|null $created_at
 * @property-read ApplicantAccount $account
 * @property-read Content|null $content
 */
class AccountConsent extends PmbModel
{
    protected $table = 'pmb_account_consents';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'document_version' => 'integer',
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
     * @return BelongsTo<Content, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}
