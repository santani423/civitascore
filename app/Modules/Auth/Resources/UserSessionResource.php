<?php

namespace Modules\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\UserSession;

/**
 * @mixin UserSession
 */
class UserSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device' => $this->whenLoaded('device', fn () => $this->device ? [
                'id' => $this->device->id,
                'device_name' => $this->device->device_name,
                'platform' => $this->device->platform,
            ] : null),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'is_active' => $this->isActive(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
