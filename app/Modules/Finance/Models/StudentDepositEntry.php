<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Enums\DepositEntryType;

/** Ledger deposit append-only (BR-KEU-51). */
class StudentDepositEntry extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'account_id', 'entry_type', 'amount', 'balance_after', 'held_after', 'reference_type', 'reference_id', 'note', 'created_by'];

    protected function casts(): array
    {
        return [
            'entry_type' => DepositEntryType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'held_after' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<StudentDepositAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(StudentDepositAccount::class, 'account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
