<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\DuplicateFlagStatus;
use Modules\Admission\Enums\DuplicateFlagType;

class DuplicateFlag extends PmbModel
{
    protected $table = 'pmb_duplicate_flags';

    protected function casts(): array
    {
        return [
            'type' => DuplicateFlagType::class,
            'status' => DuplicateFlagStatus::class,
            'similarity_score' => 'decimal:2',
            'resolved_at' => 'datetime',
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
     * @return BelongsTo<ApplicantAccount, $this>
     */
    public function relatedAccount(): BelongsTo
    {
        return $this->belongsTo(ApplicantAccount::class, 'related_account_id');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }
}
