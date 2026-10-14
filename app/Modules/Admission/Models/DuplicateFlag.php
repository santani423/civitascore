<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\DuplicateFlagStatus;
use Modules\Admission\Enums\DuplicateFlagType;

/**
 * @property string $id
 * @property string $university_id
 * @property string $applicant_account_id
 * @property string|null $related_account_id
 * @property string|null $application_id
 * @property DuplicateFlagType $type
 * @property string|null $similarity_score
 * @property DuplicateFlagStatus $status
 * @property string|null $note
 * @property string|null $resolution_note
 * @property string|null $resolved_by
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ApplicantAccount $account
 * @property-read ApplicantAccount|null $relatedAccount
 * @property-read Application|null $application
 */
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
