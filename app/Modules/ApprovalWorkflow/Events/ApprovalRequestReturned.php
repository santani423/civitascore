<?php

namespace Modules\ApprovalWorkflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;

/**
 * Pengajuan dikembalikan ke pemohon untuk direvisi.
 */
class ApprovalRequestReturned
{
    use Dispatchable;

    public function __construct(public readonly ApprovalRequest $request, public readonly string $note) {}
}
