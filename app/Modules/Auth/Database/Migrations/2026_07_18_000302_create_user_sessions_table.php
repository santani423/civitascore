<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('user_device_id')->nullable()->constrained('user_devices')->nullOnDelete();
            $table->foreignUlid('login_history_id')->nullable()->constrained('login_histories')->nullOnDelete();
            $table->string('session_token')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('last_activity_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignUlid('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index('user_id');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
