<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\SystemSetting\Enums\SettingValueType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', array_column(SettingValueType::cases(), 'value'));
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
