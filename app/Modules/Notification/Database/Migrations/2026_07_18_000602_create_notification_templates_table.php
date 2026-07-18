<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Notification\Enums\NotificationChannel;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('event_key');
            $table->string('name');
            $table->enum('channel', array_column(NotificationChannel::cases(), 'value'));
            $table->string('subject')->nullable();
            $table->text('body_template');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['event_key', 'channel'], 'notification_templates_event_key_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
