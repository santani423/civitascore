<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Admission\Support\MigrationRecovery;

/**
 * One Atma Talent — asesmen minat & bakat (docs/one-atma-talent/01 §2).
 * Versi instrumen tidak diubah setelah dipublikasikan; respons selalu menunjuk
 * butir dari versi tempat sesi dimulai, sehingga histori tidak pernah hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan sisa percobaan gagal (DDL MySQL tidak transaksional) — hanya tabel kosong.
        MigrationRecovery::clearFailedAttempt([
            'pmb_assessment_types',
            'pmb_assessment_versions',
            'pmb_assessment_dimensions',
            'pmb_assessment_items',
            'pmb_assessment_sessions',
            'pmb_assessment_responses',
            'pmb_assessment_scores',
            'pmb_assessment_session_events',
        ]);

        Schema::create('pmb_assessment_types', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('domain', 40);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['university_id', 'code']);
        });

        Schema::create('pmb_assessment_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('assessment_type_id')->constrained('pmb_assessment_types')->restrictOnDelete();
            $table->foreignUlid('period_id')->nullable()->constrained('pmb_periods')->nullOnDelete();
            $table->unsignedSmallInteger('version_no');
            $table->string('purpose', 20);
            $table->string('status', 20)->default('draft');
            $table->string('title', 150);
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->timestampTz('available_from')->nullable();
            $table->timestampTz('available_until')->nullable();
            $table->json('scoring_config');
            $table->timestampTz('published_at')->nullable();
            $table->foreignUlid('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['assessment_type_id', 'version_no']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('pmb_assessment_dimensions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('version_id')->constrained('pmb_assessment_versions')->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->text('interpretation')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();

            $table->unique(['version_id', 'code']);
        });

        Schema::create('pmb_assessment_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('version_id')->constrained('pmb_assessment_versions')->cascadeOnDelete();
            $table->foreignUlid('dimension_id')->constrained('pmb_assessment_dimensions')->cascadeOnDelete();
            $table->text('stem');
            $table->boolean('is_reversed')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['version_id', 'sort_order']);
        });

        Schema::create('pmb_assessment_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->restrictOnDelete();
            $table->foreignUlid('version_id')->constrained('pmb_assessment_versions')->restrictOnDelete();
            $table->string('status', 20)->default('in_progress');
            $table->json('item_order');
            $table->timestampTz('started_at');
            $table->timestampTz('deadline_at')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->string('holland_code', 3)->nullable();
            $table->json('dominant_dimensions')->nullable();
            $table->string('scoring_engine', 40)->nullable();
            $table->timestampTz('scored_at')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->foreignUlid('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['application_id', 'version_id']);
            $table->index(['version_id', 'status']);
        });

        Schema::create('pmb_assessment_responses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('session_id')->constrained('pmb_assessment_sessions')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('pmb_assessment_items')->restrictOnDelete();
            $table->unsignedTinyInteger('value');
            $table->timestampTz('answered_at');
            $table->timestampsTz();

            $table->unique(['session_id', 'item_id']);
        });

        Schema::create('pmb_assessment_scores', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('session_id')->constrained('pmb_assessment_sessions')->cascadeOnDelete();
            $table->foreignUlid('dimension_id')->constrained('pmb_assessment_dimensions')->restrictOnDelete();
            $table->string('dimension_code', 10);
            $table->unsignedSmallInteger('item_count');
            $table->decimal('raw_score', 8, 2);
            $table->decimal('mean_score', 5, 2);
            $table->decimal('percent_score', 5, 2);
            $table->unsignedTinyInteger('rank');
            $table->timestampsTz();

            $table->unique(['session_id', 'dimension_id']);
        });

        Schema::create('pmb_assessment_session_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('session_id')->constrained('pmb_assessment_sessions')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('actor', 100)->nullable();
            $table->json('payload')->nullable();
            $table->timestampTz('created_at');

            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmb_assessment_session_events');
        Schema::dropIfExists('pmb_assessment_scores');
        Schema::dropIfExists('pmb_assessment_responses');
        Schema::dropIfExists('pmb_assessment_sessions');
        Schema::dropIfExists('pmb_assessment_items');
        Schema::dropIfExists('pmb_assessment_dimensions');
        Schema::dropIfExists('pmb_assessment_versions');
        Schema::dropIfExists('pmb_assessment_types');
    }
};
