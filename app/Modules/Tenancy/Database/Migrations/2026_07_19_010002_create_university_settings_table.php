<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\SystemSetting\Enums\SettingValueType;

/**
 * Per-university counterpart of system_settings — same key/value/type/group
 * shape (see Modules/SystemSetting), scoped by university_id instead of
 * being a single global row per key. Covers branding, security,
 * notification, and storage configuration via the `group` column (e.g.
 * 'branding.primary_color', 'security.max_login_attempts') rather than one
 * dedicated table per concern, matching the pattern system_settings already
 * uses for global config.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->enum('type', array_column(SettingValueType::cases(), 'value'));
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['university_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_settings');
    }
};
