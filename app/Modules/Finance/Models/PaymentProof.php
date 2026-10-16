<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Models\User as FileAccessUser;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Finance\Enums\ProofStatus;

/** Bukti transfer manual (FR-PMN-03). */
class PaymentProof extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'payment_transaction_id', 'file_upload_id', 'sender_bank', 'sender_name', 'transfer_date', 'transferred_amount', 'checksum', 'status', 'reason_code', 'note', 'duplicate_warning', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'transferred_amount' => 'decimal:2',
            'status' => ProofStatus::class,
            'duplicate_warning' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PaymentTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /** @return BelongsTo<FileUpload, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'file_upload_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Berkas bukti hanya untuk petugas berizin dan mahasiswa pemiliknya (BR-KEU-72). */
    public function allowsFileAccess(FileAccessUser $user): bool
    {
        $ownerUserId = $this->transaction?->student?->user_id;

        return $user->hasPermissionTo('payment_transactions.read') || ($ownerUserId !== null && $ownerUserId === $user->id);
    }
}
