<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Models\User as FileAccessUser;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Finance\Enums\AidAwardStatus;
use Modules\Finance\Enums\AidCalcType;

/** Penetapan bantuan pada tagihan mahasiswa (FR-BSW-01). */
class FinancialAidAward extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'student_id', 'discount_policy_id', 'calc_type', 'value', 'max_amount', 'fee_component_ids', 'start_term_id', 'end_term_id', 'status', 'basis', 'evidence_file_id', 'requested_by', 'decided_by', 'decided_at', 'decision_note', 'revoked_by', 'revoked_at', 'revoke_reason'];

    protected function casts(): array
    {
        return [
            'calc_type' => AidCalcType::class,
            'value' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'fee_component_ids' => 'array',
            'status' => AidAwardStatus::class,
            'decided_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<DiscountPolicy, $this> */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(DiscountPolicy::class, 'discount_policy_id');
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function startTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'start_term_id');
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function endTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'end_term_id');
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
        $ownerUserId = $this->student?->user_id;

        return $user->hasPermissionTo('financial_aid.read') || ($ownerUserId !== null && $ownerUserId === $user->id);
    }
}
