<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableUlidMorphs('subject');
            // Open-ended channel tag (e.g. "auth", "billing") that future
            // modules append new values to — intentionally not an enum, so
            // adding a channel never requires a migration.
            $table->string('log_name')->default('default');
            $table->text('description');
            $table->json('properties')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index('log_name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
