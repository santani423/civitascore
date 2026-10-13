<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PMB — komunikasi & tabel lintas (docs/pmb/10 §4.10) + tabel pendukung
 * implementasi (idempotensi, permintaan perubahan ber-approval).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pmb_broadcasts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('channel', 20);
            $table->json('audience_filter');
            $table->string('content_subject', 250)->nullable();
            $table->text('content_body');
            $table->string('status', 20)->default('draft');
            $table->timestampTz('scheduled_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['university_id', 'status']);
        });

        Schema::create('pmb_chat_threads', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->constrained('pmb_applicant_accounts')->cascadeOnDelete();
            $table->foreignUlid('application_id')->nullable()->constrained('pmb_applications')->nullOnDelete();
            $table->string('subject', 200);
            $table->string('status', 20)->default('open');
            $table->foreignUlid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('last_message_at')->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'status', 'last_message_at']);
        });

        Schema::create('pmb_chat_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('thread_id')->constrained('pmb_chat_threads')->cascadeOnDelete();
            $table->string('sender_type', 20);
            $table->ulid('sender_id')->nullable();
            $table->text('body');
            $table->foreignUlid('attachment_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
            $table->index(['thread_id', 'created_at']);
        });

        Schema::create('pmb_export_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('report_code', 60);
            $table->string('format', 10);
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->string('reason', 500);
            $table->foreignUlid('file_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->boolean('contains_personal_data')->default(false);
            $table->string('status', 20)->default('pending');
            $table->timestampTz('expires_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'user_id', 'created_at']);
        });

        Schema::create('pmb_sensitive_access_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject_type', 60);
            $table->ulid('subject_id');
            $table->string('field_or_document', 100);
            $table->string('purpose', 500);
            $table->string('ip_address', 45)->nullable();
            $table->timestampTz('created_at')->nullable();
            $table->index(['university_id', 'subject_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('pmb_outbox_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('event_type', 80);
            $table->string('aggregate_type', 60);
            $table->ulid('aggregate_id');
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('available_at');
            $table->timestampTz('dispatched_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'available_at']);
        });

        Schema::create('pmb_daily_stats', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->date('stat_date');
            $table->foreignUlid('period_id')->constrained('pmb_periods')->cascadeOnDelete();
            // String kosong '' (bukan NULL) sebagai "semua" agar unique tetap menahan duplikat.
            $table->string('wave_id', 26)->default('');
            $table->string('admission_path_id', 26)->default('');
            $table->string('study_program_id', 26)->default('');
            $table->string('metric', 40);
            $table->decimal('value', 14, 2)->default(0);
            $table->timestampsTz();
            $table->unique(['university_id', 'stat_date', 'period_id', 'wave_id', 'admission_path_id', 'study_program_id', 'metric'], 'pmb_daily_stats_unique');
        });

        // Tambahan implementasi: penyimpanan kunci idempotensi (11 §A.1) di DB
        // agar tidak bergantung pada Redis (lingkungan shared hosting).
        Schema::create('pmb_idempotency_keys', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('scope', 120);
            $table->string('key', 100);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestampTz('expires_at');
            $table->timestampsTz();
            $table->unique(['scope', 'key']);
            $table->index('expires_at');
        });

        // Tambahan implementasi: objek yang diajukan ke approval engine untuk
        // koreksi nilai/hasil, pembatalan daftar ulang, perpanjangan massal,
        // percepatan publikasi (workflow pmb_* di 10 §6).
        Schema::create('pmb_change_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('subject_type', 60)->nullable();
            $table->ulid('subject_id')->nullable();
            $table->json('payload');
            $table->text('reason');
            $table->foreignUlid('evidence_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('status', 20)->default('pending_approval');
            $table->ulid('approval_request_id')->nullable()->index();
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampTz('applied_at')->nullable();
            $table->text('result_note')->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        foreach ([
            'pmb_change_requests', 'pmb_idempotency_keys', 'pmb_daily_stats', 'pmb_outbox_events',
            'pmb_sensitive_access_logs', 'pmb_export_logs', 'pmb_chat_messages', 'pmb_chat_threads', 'pmb_broadcasts',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
