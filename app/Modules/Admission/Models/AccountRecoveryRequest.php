<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\RecoveryRequestStatus;
use Modules\FileManagement\Models\FileUpload;

/**
 * @property string $id
 * @property string $university_id
 * @property string|null $applicant_account_id
 * @property string $full_name
 * @property CarbonImmutable $birth_date
 * @property string $identity_hash
 * @property string $new_email
 * @property string|null $identity_file_id
 * @property RecoveryRequestStatus $status
 * @property string|null $resolution_note
 * @property string|null $resolved_by
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ApplicantAccount|null $account
 * @property-read FileUpload|null $identityFile
 */
class AccountRecoveryRequest extends PmbModel
{
    protected $table = 'pmb_account_recovery_requests';

    /** @var array<int, string> */
    protected $hidden = ['identity_hash'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'status' => RecoveryRequestStatus::class,
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
     * @return BelongsTo<FileUpload, $this>
     */
    public function identityFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'identity_file_id');
    }
}
