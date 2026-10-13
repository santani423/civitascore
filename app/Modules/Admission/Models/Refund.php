<?php

namespace Modules\Admission\Models;

use Modules\ApprovalWorkflow\Contracts\RequiresDistinctApprover;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\RefundStatus;

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
