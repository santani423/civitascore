<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Enums\LoginFailureReason;
use Modules\Auth\Enums\LoginHistoryStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email_attempted')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignUlid('user_device_id')->nullable()->constrained('user_devices')->nullOnDelete();
            $table->enum('status', array_column(LoginHistoryStatus::cases(), 'value'));
            $table->enum('failure_reason', array_column(LoginFailureReason::cases(), 'value'))->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
