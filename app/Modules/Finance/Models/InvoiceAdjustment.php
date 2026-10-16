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
use Modules\Finance\Enums\AdjustmentType;
use Modules\Finance\Enums\ApprovalStatus;

/** Penyesuaian tagihan beralasan & berpersetujuan (KD-KEU-04). */
class InvoiceAdjustment extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'adjustment_number', 'invoice_id', 'type', 'amount', 'reason_code', 'reason_note', 'evidence_file_id', 'status', 'requested_by', 'decided_by', 'decided_at', 'decision_note', 'applied_at'];

    protected function casts(): array
    {
        return [
            'type' => AdjustmentType::class,
            'status' => ApprovalStatus::class,
            'amount' => 'decimal:2',
            'decided_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return BelongsTo<FileUpload, $this> */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'evidence_file_id');
    }

    /** Berkas bukti hanya untuk petugas berizin dan mahasiswa pemiliknya (BR-KEU-72). */
    public function allowsFileAccess(FileAccessUser $user): bool
    {
        $ownerUserId = null;

        return $user->hasPermissionTo('invoice_adjustments.read') || ($ownerUserId !== null && $ownerUserId === $user->id);
    }
}
