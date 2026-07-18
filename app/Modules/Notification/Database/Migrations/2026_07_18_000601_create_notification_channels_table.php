<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Notification\Enums\NotificationChannel;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('code', array_column(NotificationChannel::cases(), 'value'))->unique();
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
