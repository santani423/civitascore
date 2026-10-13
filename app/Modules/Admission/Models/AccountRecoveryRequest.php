<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\RecoveryRequestStatus;
use Modules\FileManagement\Models\FileUpload;

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
