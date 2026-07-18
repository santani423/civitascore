<?php

namespace Modules\ApprovalWorkflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;

class ApprovalRequestStepAdvanced
{
    use Dispatchable;

    public function __construct(
        public readonly ApprovalRequest $request,
        public readonly ApprovalRequestStep $step,
    ) {}
}
