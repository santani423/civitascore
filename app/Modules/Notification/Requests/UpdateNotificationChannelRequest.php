<?php

namespace Modules\Notification\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Notification\Models\NotificationChannelConfig;

class UpdateNotificationChannelRequest extends FormRequest
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
            'is_enabled' => ['required', 'boolean', function (string $attribute, mixed $value, Closure $fail): void {
                $channel = $this->route('notificationChannel');

                if ($value && $channel instanceof NotificationChannelConfig && ! $channel->code->isImplemented()) {
                    $fail("Channel notifikasi '{$channel->code->value}' belum memiliki implementasi pengiriman dan tidak dapat diaktifkan.");
                }
            }],
        ];
    }
}
