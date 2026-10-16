<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\Student;

/** Akun saldo titipan mahasiswa (FR-DEP-01). */
class StudentDepositAccount extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'student_id', 'balance', 'held_amount', 'lock_version'];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'held_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return HasMany<StudentDepositEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(StudentDepositEntry::class, 'account_id');
    }
}
