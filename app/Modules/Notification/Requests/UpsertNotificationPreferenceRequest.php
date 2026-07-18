<?php

namespace Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Notification\Enums\NotificationChannel;

class UpsertNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', new Enum(NotificationChannel::class)],
            'notification_type' => ['sometimes', 'string', 'max:191'],
            'is_enabled' => ['required', 'boolean'],
        ];
    }
}
