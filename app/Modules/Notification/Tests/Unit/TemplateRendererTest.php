<?php

use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Services\TemplateRenderer;

test('it renders a known event_key and channel by substituting placeholders', function () {
    NotificationTemplate::factory()->create([
        'event_key' => 'demo.greeting',
        'channel' => NotificationChannel::Database,
        'body_template' => 'Hello {{name}}, welcome to {{app}}.',
        'is_active' => true,
    ]);

    $rendered = app(TemplateRenderer::class)->render('demo.greeting', NotificationChannel::Database, [
        'name' => 'Budi',
        'app' => 'CivitasOne',
    ]);

    expect($rendered)->toBe('Hello Budi, welcome to CivitasOne.');
});

test('it returns null for an unknown event_key', function () {
    $rendered = app(TemplateRenderer::class)->render('unknown.event', NotificationChannel::Database);

    expect($rendered)->toBeNull();
});

test('it ignores an inactive template', function () {
    NotificationTemplate::factory()->create([
        'event_key' => 'demo.inactive',
        'channel' => NotificationChannel::Mail,
        'is_active' => false,
    ]);

    $rendered = app(TemplateRenderer::class)->render('demo.inactive', NotificationChannel::Mail);

    expect($rendered)->toBeNull();
});
