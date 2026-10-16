<?php

namespace Modules\Academic\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Enums\AcademicBlockStatus;

/**
 * Cekal layanan akademik (A.8). Diterbitkan sumber (`finance`, kelak
 * `library`/`attendance`/`manual`), ditegakkan AcademicBlockService::check()
 * di titik layanan (KRS, ujian). `details` milik sumber — nominal keuangan
 * hanya ditampilkan ke mahasiswa & Keuangan (BR-SIA-CKL-09).
 */
class AcademicBlock extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'student_id', 'academic_term_id', 'block_type', 'source', 'source_rule_code', 'restricted_services',
        'reason', 'details', 'status', 'active_key', 'effective_from', 'imposed_by', 'revoked_by', 'revoked_at', 'revoke_reason',
    ];

    protected function casts(): array
    {
        return [
            'restricted_services' => 'array',
            'details' => 'array',
            'status' => AcademicBlockStatus::class,
            'effective_from' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /** @return HasMany<AcademicBlockExemption, $this> */
    public function exemptions(): HasMany
    {
        return $this->hasMany(AcademicBlockExemption::class);
    }

    /** @return HasMany<AcademicBlockHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(AcademicBlockHistory::class)->orderBy('created_at');
    }

    /** @return BelongsTo<User, $this> */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
