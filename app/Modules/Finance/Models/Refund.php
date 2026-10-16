<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Models\User as FileAccessUser;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Student;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;
use Modules\Finance\Enums\RefundStatus;

/** Pengembalian dana dari saldo deposit (FR-RFD-05, KD-KEU-08). Rekening tujuan terenkripsi (BR-KEU-72). */
class Refund extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'refund_number', 'student_id', 'source_type', 'amount', 'reason_code', 'reason_note', 'beneficiary_bank', 'beneficiary_account', 'beneficiary_name', 'beneficiary_relation', 'status', 'requested_by', 'decided_by', 'decided_at', 'decision_note', 'executed_by', 'executed_at', 'execution_reference', 'proof_file_id', 'failure_reason'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RefundStatus::class,
            'beneficiary_account' => 'encrypted',
            'beneficiary_name' => 'encrypted',
            'decided_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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

    /** @return BelongsTo<User, $this> */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    /** @return BelongsTo<FileUpload, $this> */
    public function proof(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'proof_file_id');
    }

    /** Berkas bukti hanya untuk petugas berizin dan mahasiswa pemiliknya (BR-KEU-72). */
    public function allowsFileAccess(FileAccessUser $user): bool
    {
        $ownerUserId = $this->student?->user_id;

        return $user->hasPermissionTo('refunds.read') || ($ownerUserId !== null && $ownerUserId === $user->id);
    }
}
