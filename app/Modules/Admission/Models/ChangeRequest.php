<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ChangeRequestStatus;
use Modules\Admission\Enums\ChangeRequestType;
use Modules\ApprovalWorkflow\Contracts\RequiresDistinctApprover;
use Modules\FileManagement\Models\FileUpload;

/**
 * @property string $id
 * @property string $university_id
 * @property ChangeRequestType $type
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property array<mixed> $payload
 * @property string $reason
 * @property string|null $evidence_file_id
 * @property ChangeRequestStatus $status
 * @property string|null $approval_request_id
 * @property string $requested_by
 * @property CarbonImmutable|null $decided_at
 * @property CarbonImmutable|null $applied_at
 * @property string|null $result_note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $requester
 * @property-read FileUpload|null $evidence
 */
class ChangeRequest extends PmbModel implements RequiresDistinctApprover
{
    protected $table = 'pmb_change_requests';

    protected function casts(): array
    {
        return [
            'type' => ChangeRequestType::class,
            'status' => ChangeRequestStatus::class,
            'payload' => 'array',
            'decided_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'evidence_file_id');
    }

    public function excludedApproverIds(): array
    {
        return [(string) $this->requested_by];
    }
}
