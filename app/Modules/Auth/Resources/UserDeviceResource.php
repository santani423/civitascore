<?php

namespace Modules\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\UserDevice;

/**
 * @mixin UserDevice
 */
class UserDeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_name' => $this->device_name,
            'device_type' => $this->device_type->value,
            'platform' => $this->platform,
            'is_trusted' => $this->is_trusted,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
        ];
    }
}
