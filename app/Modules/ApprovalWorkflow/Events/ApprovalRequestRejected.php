<?php

namespace Modules\ApprovalWorkflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;

class ApprovalRequestRejected
{
    use Dispatchable;

    public function __construct(public readonly ApprovalRequest $request) {}
}
