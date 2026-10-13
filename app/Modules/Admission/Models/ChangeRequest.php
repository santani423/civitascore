<?php

namespace Modules\Admission\Models;

use Modules\ApprovalWorkflow\Contracts\RequiresDistinctApprover;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ChangeRequestStatus;
use Modules\Admission\Enums\ChangeRequestType;
use Modules\FileManagement\Models\FileUpload;

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
