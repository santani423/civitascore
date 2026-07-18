<?php

namespace Modules\Notification\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\Models\NotificationChannelConfig;

/**
 * @mixin NotificationChannelConfig
 */
class NotificationChannelConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code->value,
            'name' => $this->name,
            'is_enabled' => $this->is_enabled,
        ];
    }
}
