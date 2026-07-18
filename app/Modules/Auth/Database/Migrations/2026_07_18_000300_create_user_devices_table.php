<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Enums\DeviceType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_identifier');
            $table->string('device_name')->nullable();
            $table->enum('device_type', array_column(DeviceType::cases(), 'value'))->default(DeviceType::Unknown->value);
            $table->string('platform')->nullable();
            $table->string('push_token')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['user_id', 'device_identifier'], 'user_devices_user_id_device_identifier_unique');
            $table->index('device_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
