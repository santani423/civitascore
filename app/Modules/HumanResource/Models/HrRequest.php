<?php

namespace Modules\HumanResource\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\AuditLog\Support\Auditable;
use Modules\FileManagement\Models\FileUpload;
use Modules\HumanResource\Enums\HrRequestStatus;
use Modules\HumanResource\Enums\HrRequestType;
use Modules\HumanResource\Models\Concerns\BelongsToEmployee;

/**
 * Satu pintu seluruh pengajuan SDM. Model ini yang dikirim ke
 * ApprovalRequestService::submit() sebagai `requestable`, sehingga alur
 * persetujuannya memakai Modul ApprovalWorkflow yang sudah ada.
 *
 * @property string $id
 * @property string $university_id
 * @property string $employee_id
 * @property HrRequestType $type
 * @property string $title
 * @property string|null $description
 * @property array<string, mixed>|null $payload
 * @property string|null $leave_request_id
 * @property string|null $attachment_file_id
 * @property HrRequestStatus $status
 * @property string|null $requested_by
 * @property string|null $approval_request_id
 * @property string|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property string|null $rejected_by
 * @property CarbonImmutable|null $rejected_at
 * @property string|null $approval_note
 * @property CarbonImmutable|null $processed_at
 * @property string|null $processed_by
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read LeaveRequest|null $leaveRequest
 * @property-read ApprovalRequest|null $approvalRequest
 * @property-read FileUpload|null $attachmentFile
 * @property-read User|null $requester
 * @property-read User|null $approver
 * @property-read User|null $rejecter
 * @property-read User|null $processor
 */
class HrRequest extends Model implements ScopesToInstitution
{
    use Auditable, BelongsToEmployee, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'employee_id', 'type', 'title', 'description', 'payload', 'leave_request_id',
        'attachment_file_id', 'status', 'requested_by', 'approval_request_id', 'approved_by', 'approved_at',
        'rejected_by', 'rejected_at', 'approval_note', 'processed_at', 'processed_by', 'cancelled_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'type' => HrRequestType::class,
            'status' => HrRequestStatus::class,
            'payload' => 'array',
            'approved_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    /**
     * Jenis pengajuan yang setelah disetujui masih perlu ditindaklanjuti
     * manual oleh SDM (membuat SK/mutasi/dokumen) — dasar alert
     * "perlu diproses" di dashboard.
     */
    public function requiresProcessing(): bool
    {
        return in_array($this->type, [HrRequestType::Transfer, HrRequestType::Promotion, HrRequestType::Document], true);
    }

    /**
     * @return BelongsTo<LeaveRequest, $this>
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * @return BelongsTo<ApprovalRequest, $this>
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
