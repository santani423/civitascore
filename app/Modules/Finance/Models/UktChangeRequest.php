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
use Modules\Finance\Enums\UktRequestStatus;

/** Pengajuan perubahan golongan UKT (FR-UKT-06). */
class UktChangeRequest extends Model implements RestrictsFileAccess, ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'student_id', 'current_group_id', 'requested_group_id', 'reason', 'evidence_file_id', 'status', 'requested_by', 'decided_group_id', 'decided_by', 'decided_at', 'decision_note', 'effective_term_id', 'assignment_id'];

    protected function casts(): array
    {
        return [
            'status' => UktRequestStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<UktGroup, $this> */
    public function currentGroup(): BelongsTo
    {
        return $this->belongsTo(UktGroup::class, 'current_group_id');
    }

    /** @return BelongsTo<UktGroup, $this> */
    public function requestedGroup(): BelongsTo
    {
        return $this->belongsTo(UktGroup::class, 'requested_group_id');
    }

    /** @return BelongsTo<UktGroup, $this> */
    public function decidedGroup(): BelongsTo
    {
        return $this->belongsTo(UktGroup::class, 'decided_group_id');
    }

    /** @return BelongsTo<FileUpload, $this> */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'evidence_file_id');
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

    /** @return BelongsTo<AcademicTerm, $this> */
    public function effectiveTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'effective_term_id');
    }

    /** Berkas bukti hanya untuk petugas berizin dan mahasiswa pemiliknya (BR-KEU-72). */
    public function allowsFileAccess(FileAccessUser $user): bool
    {
        $ownerUserId = $this->student?->user_id;

        return $user->hasPermissionTo('ukt.read') || ($ownerUserId !== null && $ownerUserId === $user->id);
    }
}
