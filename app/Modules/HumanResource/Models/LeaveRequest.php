<?php

namespace Modules\HumanResource\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\AuditLog\Support\Auditable;
use Modules\FileManagement\Models\FileUpload;
use Modules\HumanResource\Database\Factories\LeaveRequestFactory;
use Modules\HumanResource\Enums\LeaveStatus;
use Modules\HumanResource\Enums\LeaveType;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;

/**
 * Detail cuti/izin. Proses persetujuannya berjalan lewat HrRequest
 * (type = leave) yang terhubung 1:1 — status di sini disinkronkan oleh
 * HrRequestService saat keputusan diambil.
 *
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property LeaveType $leave_type
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property int $days
 * @property string $reason
 * @property string|null $attachment_file_id
 * @property LeaveStatus $status
 * @property string|null $requested_by
 * @property CarbonImmutable|null $submitted_at
 * @property string|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property string|null $rejected_by
 * @property CarbonImmutable|null $rejected_at
 * @property string|null $approval_note
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read HrRequest|null $hrRequest
 * @property-read FileUpload|null $attachmentFile
 * @property-read User|null $requester
 * @property-read User|null $approver
 * @property-read User|null $rejecter
 */
class LeaveRequest extends Model implements ScopesToInstitution
{
    /** @use HasFactory<LeaveRequestFactory> */
    use Auditable, BelongsToEmployee, HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'leave_type', 'start_date', 'end_date', 'days', 'reason', 'attachment_file_id',
        'status', 'requested_by', 'submitted_at', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at',
        'approval_note', 'cancelled_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'leave_type' => LeaveType::class,
            'status' => LeaveStatus::class,
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'days' => 'integer',
            'submitted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasOne<HrRequest, $this>
     */
    public function hrRequest(): HasOne
    {
        return $this->hasOne(HrRequest::class);
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function attachmentFile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'attachment_file_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    protected static function newFactory(): LeaveRequestFactory
    {
        return LeaveRequestFactory::new();
    }
}
