<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Enums\NotificationLogStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Deliberately no FK to notifications.id — log retention must
            // not be coupled to the notifications table's own pruning.
            $table->ulid('notification_id')->nullable();
            $table->ulidMorphs('notifiable');
            $table->enum('channel', array_column(NotificationChannel::cases(), 'value'));
            $table->string('event_key');
            $table->enum('status', array_column(NotificationLogStatus::cases(), 'value'));
            $table->text('error_message')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->index('channel');
            $table->index('status');
            $table->index('event_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
