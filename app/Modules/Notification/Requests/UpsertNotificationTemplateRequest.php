<?php

namespace Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Notification\Enums\NotificationChannel;

class UpsertNotificationTemplateRequest extends FormRequest
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
            'event_key' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:150'],
            'channel' => ['required', new Enum(NotificationChannel::class)],
            'subject' => ['nullable', 'string', 'max:200'],
            'body_template' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
