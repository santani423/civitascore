<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Admission\Support\MigrationRecovery;

/**
 * One Atma Talent — Fase 2 (docs/one-atma-talent/03–06). Hanya menambah kolom dan tabel,
 * kecuali satu pembaruan status `retired` → `archived` yang tidak mengubah data lain.
 * Tidak ada kolom yang dihapus; histori respons dan sesi tetap utuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        MigrationRecovery::clearFailedAttempt(['pmb_assessment_review_notes']);

        Schema::table('pmb_assessment_items', function (Blueprint $table): void {
            $table->string('code', 30)->nullable();
            $table->string('question_type', 20)->default('likert');
            $table->string('status', 20)->default('draft');
            $table->foreignUlid('source_item_id')->nullable()->constrained('pmb_assessment_items')->nullOnDelete();
            $table->unique(['version_id', 'code']);
        });

        Schema::table('pmb_assessment_versions', function (Blueprint $table): void {
            $table->foreignUlid('parent_version_id')->nullable()->constrained('pmb_assessment_versions')->nullOnDelete();
            $table->foreignUlid('approval_request_id')->nullable()->constrained('approval_requests')->nullOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignUlid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->text('change_note')->nullable();
        });

        // Status `retired` (Slice 1) diganti nama menjadi `archived` sesuai rancangan Fase 2.
        DB::table('pmb_assessment_versions')->where('status', 'retired')->update(['status' => 'archived', 'archived_at' => now()]);

        Schema::table('pmb_assessment_sessions', function (Blueprint $table): void {
            $table->string('review_status', 20)->default('none');
            $table->string('mode', 20)->default('online');
            $table->timestampTz('invalidated_at')->nullable();
            $table->foreignUlid('invalidated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('invalidation_reason')->nullable();
            $table->index(['review_status']);
        });

        Schema::create('pmb_assessment_review_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('session_id')->constrained('pmb_assessment_sessions')->cascadeOnDelete();
            $table->foreignUlid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 20);
            $table->text('body')->nullable();
            $table->timestampTz('created_at');

            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmb_assessment_review_notes');

        Schema::table('pmb_assessment_sessions', function (Blueprint $table): void {
            $table->dropIndex(['review_status']);
            $table->dropConstrainedForeignId('invalidated_by');
            $table->dropColumn(['review_status', 'mode', 'invalidated_at', 'invalidation_reason']);
        });

        DB::table('pmb_assessment_versions')->where('status', 'archived')->update(['status' => 'retired']);

        Schema::table('pmb_assessment_versions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_version_id');
            $table->dropConstrainedForeignId('approval_request_id');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn(['submitted_at', 'approved_at', 'archived_at', 'change_note']);
        });

        Schema::table('pmb_assessment_items', function (Blueprint $table): void {
            $table->dropUnique(['version_id', 'code']);
            $table->dropConstrainedForeignId('source_item_id');
            $table->dropColumn(['code', 'question_type', 'status']);
        });
    }
};
