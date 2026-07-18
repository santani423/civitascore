<?php

namespace Modules\Notification\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\Models\UserNotificationPreference;

/**
 * @mixin UserNotificationPreference
 */
class UserNotificationPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel->value,
            'notification_type' => $this->notification_type,
            'is_enabled' => $this->is_enabled,
        ];
    }
}
