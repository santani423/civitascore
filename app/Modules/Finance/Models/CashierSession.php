<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Models\User as FileAccessUser;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Finance\Enums\CashierSessionStatus;

/** Sesi kas loket (FR-PMN-01). */
class CashierSession extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'session_number', 'cashier_id', 'opened_at', 'opening_float', 'closed_at', 'system_total', 'counted_total', 'difference', 'deposit_reference', 'deposit_file_id', 'status', 'verified_by', 'verified_at', 'note'];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'verified_at' => 'datetime',
            'opening_float' => 'decimal:2',
            'system_total' => 'decimal:2',
            'counted_total' => 'decimal:2',
            'difference' => 'decimal:2',
            'status' => CashierSessionStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return BelongsTo<FileUpload, $this> */
    public function depositFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'deposit_file_id');
    }

    /** @return HasMany<PaymentTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /** Berkas bukti hanya untuk petugas berizin dan mahasiswa pemiliknya (BR-KEU-72). */
    public function allowsFileAccess(FileAccessUser $user): bool
    {
        $ownerUserId = null;

        return $user->hasPermissionTo('cashier_sessions.read') || ($ownerUserId !== null && $ownerUserId === $user->id);
    }
}
