<?php

namespace Modules\ApprovalWorkflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;

class ApprovalRequestSubmitted
{
    use Dispatchable;

    public function __construct(public readonly ApprovalRequest $request) {}
}
