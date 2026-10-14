<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\RefundStatus;
use Modules\ApprovalWorkflow\Contracts\RequiresDistinctApprover;

/**
 * @property string $id
 * @property string $university_id
 * @property string $transaction_id
 * @property string $amount
 * @property string $reason_code
 * @property string $reason_note
 * @property string $beneficiary_bank
 * @property string $beneficiary_account
 * @property string $beneficiary_name
 * @property RefundStatus $status
 * @property string|null $approval_request_id
 * @property string|null $requested_by
 * @property CarbonImmutable|null $completed_at
 * @property string|null $proof_file_id
 * @property string|null $batch_reference
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PaymentTransaction $transaction
 * @property-read User|null $requester
 */
class Refund extends PmbModel implements RequiresDistinctApprover
{
    protected $table = 'pmb_refunds';

    /** @var array<int, string> */
    protected $hidden = ['beneficiary_account'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RefundStatus::class,
            'beneficiary_bank' => 'encrypted',
            'beneficiary_account' => 'encrypted',
            'beneficiary_name' => 'encrypted',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** BR-20: verifikator bukti bayar manual transaksi ini tidak boleh menyetujui refund-nya. */
    public function excludedApproverIds(): array
    {
        $reviewer = ManualPaymentProof::query()->where('transaction_id', $this->transaction_id)->value('reviewed_by');

        return $reviewer ? [(string) $reviewer] : [];
    }
}
