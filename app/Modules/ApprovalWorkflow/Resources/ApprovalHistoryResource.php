<?php

namespace Modules\ApprovalWorkflow\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ApprovalWorkflow\Models\ApprovalHistory;

/**
 * @mixin ApprovalHistory
 */
class ApprovalHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event->value,
            'actor_id' => $this->actor_id,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
