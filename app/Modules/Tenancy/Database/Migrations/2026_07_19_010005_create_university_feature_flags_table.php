<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-university override of the global feature_flags table (Modules/SystemSetting).
 * A key present here wins over the global flag of the same key for that
 * university; a key absent here falls back to the global value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_feature_flags', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('key');
            $table->boolean('is_enabled')->default(false);
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['university_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_feature_flags');
    }
};
