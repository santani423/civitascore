<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\SystemSetting\Enums\SettingValueType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('module_key');
            $table->string('key');
            $table->text('value')->nullable();
            $table->enum('type', array_column(SettingValueType::cases(), 'value'));
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_editable')->default(true);
            $table->timestampTz('available_from')->nullable();
            $table->timestampTz('available_until')->nullable();
            $table->timestampsTz();

            $table->unique(['module_key', 'key'], 'module_settings_module_key_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_settings');
    }
};
