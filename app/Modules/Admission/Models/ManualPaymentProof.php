<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ManualProofStatus;
use Modules\FileManagement\Models\FileUpload;

class ManualPaymentProof extends PmbModel
{
    protected $table = 'pmb_manual_payment_proofs';

    protected function casts(): array
    {
        return [
            'status' => ManualProofStatus::class,
            'transfer_date' => 'date',
            'transferred_amount' => 'decimal:2',
            'duplicate_warning' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PaymentTransaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'file_upload_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
