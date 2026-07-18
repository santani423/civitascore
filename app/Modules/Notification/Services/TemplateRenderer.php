<?php

namespace Modules\Notification\Services;

use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationTemplate;

class TemplateRenderer
{
    /**
     * @param  array<string, string>  $placeholders
     */
    public function render(string $eventKey, NotificationChannel $channel, array $placeholders = []): ?string
    {
        $template = NotificationTemplate::query()
            ->where('event_key', $eventKey)
            ->where('channel', $channel->value)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return null;
        }

        return strtr($template->body_template, $this->wrapPlaceholders($placeholders));
    }

    /**
     * @param  array<string, string>  $placeholders
     * @return array<string, string>
     */
    private function wrapPlaceholders(array $placeholders): array
    {
        $wrapped = [];

        foreach ($placeholders as $key => $value) {
            $wrapped['{{'.$key.'}}'] = $value;
        }

        return $wrapped;
    }
}
