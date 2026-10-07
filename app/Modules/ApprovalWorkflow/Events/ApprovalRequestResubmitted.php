<?php

namespace Modules\ApprovalWorkflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;

/**
 * Pengajuan yang dikembalikan telah direvisi dan diajukan ulang — alur
 * dimulai lagi dari langkah pertama.
 */
class ApprovalRequestResubmitted
{
    use Dispatchable;

    public function __construct(public readonly ApprovalRequest $request) {}
}
