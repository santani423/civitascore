<?php

namespace Modules\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\LoginHistory;

/**
 * @mixin LoginHistory
 */
class LoginHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email_attempted' => $this->email_attempted,
            'ip_address' => $this->ip_address,
            'status' => $this->status->value,
            'failure_reason' => $this->failure_reason?->value,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
