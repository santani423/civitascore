<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
