<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ManualProofStatus;
use Modules\FileManagement\Models\FileUpload;

/**
 * @property string $id
 * @property string $university_id
 * @property string $transaction_id
 * @property string $file_upload_id
 * @property string $sender_bank
 * @property string $sender_name
 * @property CarbonImmutable $transfer_date
 * @property string $transferred_amount
 * @property ManualProofStatus $status
 * @property string|null $reason_code
 * @property string|null $note
 * @property bool $duplicate_warning
 * @property string|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PaymentTransaction $transaction
 * @property-read FileUpload $fileUpload
 * @property-read User|null $reviewer
 */
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
