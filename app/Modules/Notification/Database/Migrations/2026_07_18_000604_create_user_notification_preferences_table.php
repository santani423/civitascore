<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Notification\Enums\NotificationChannel;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('channel', array_column(NotificationChannel::cases(), 'value'));
            // '*' sentinel (not null) avoids NULL-uniqueness ambiguity
            // across Postgres/SQLite when no specific notification_type
            // override exists yet — it means "every type on this channel".
            $table->string('notification_type')->default('*');
            $table->boolean('is_enabled')->default(true);
            $table->timestampsTz();

            $table->unique(
                ['user_id', 'channel', 'notification_type'],
                'user_notification_preferences_user_id_channel_type_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
