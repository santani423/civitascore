<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Admission\Support\MigrationRecovery;

/**
 * PMB — ujian, CBT, seleksi, keputusan, daftar ulang & konversi
 * (docs/pmb/10 §4.6–4.9, langkah 8–9 urutan migrasi).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan sisa percobaan gagal (DDL MySQL tidak transaksional) — hanya tabel kosong.
        MigrationRecovery::clearFailedAttempt([
            'pmb_exam_venues',
            'pmb_exam_rooms',
            'pmb_exam_schedules',
            'pmb_exam_sessions',
            'pmb_exam_session_examiners',
            'pmb_exam_participants',
            'pmb_exam_reschedule_requests',
            'pmb_cbt_questions',
            'pmb_cbt_attempts',
            'pmb_selection_runs',
            'pmb_selection_scores',
            'pmb_score_revisions',
            'pmb_score_imports',
            'pmb_selection_run_results',
            'pmb_announcement_batches',
            'pmb_admission_decisions',
            'pmb_acceptance_letters',
            'pmb_re_registrations',
            'pmb_re_registration_checks',
            'pmb_sequences',
            'pmb_student_conversions',
        ]);

        Schema::create('pmb_exam_venues', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('address')->nullable();
            $table->string('map_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('pmb_exam_rooms', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('venue_id')->constrained('pmb_exam_venues')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->unsignedInteger('capacity');
            $table->boolean('is_accessible')->default(false);
            $table->json('facilities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['venue_id', 'code']);
        });

        Schema::create('pmb_exam_schedules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('wave_id')->constrained('pmb_waves')->cascadeOnDelete();
            $table->foreignUlid('selection_component_id')->constrained('pmb_selection_components')->restrictOnDelete();
            $table->json('wave_path_ids');
            $table->string('mode', 30);
            $table->string('title', 200);
            $table->date('exam_date');
            $table->text('instructions')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('external_reference', 100)->nullable();
            $table->unsignedSmallInteger('cbt_duration_minutes')->nullable();
            $table->unsignedSmallInteger('cbt_question_count')->nullable();
            $table->boolean('cbt_randomize')->default(true);
            $table->timestampTz('published_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['university_id', 'wave_id', 'status']);
        });

        Schema::create('pmb_exam_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('schedule_id')->constrained('pmb_exam_schedules')->cascadeOnDelete();
            $table->foreignUlid('room_id')->nullable()->constrained('pmb_exam_rooms')->restrictOnDelete();
            $table->string('code', 30);
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->timestampTz('checkin_opens_at')->nullable();
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('participants_count')->default(0);
            $table->string('online_url', 500)->nullable();
            $table->timestampTz('scores_locked_at')->nullable();
            $table->foreignUlid('scores_locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestampsTz();
            $table->unique(['schedule_id', 'code']);
            $table->index(['room_id', 'starts_at']);
        });

        Schema::create('pmb_exam_session_examiners', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('session_id')->constrained('pmb_exam_sessions')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 20);
            $table->timestampsTz();
            $table->unique(['session_id', 'user_id', 'role']);
        });

        Schema::create('pmb_exam_participants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('schedule_id')->constrained('pmb_exam_schedules')->cascadeOnDelete();
            $table->foreignUlid('session_id')->constrained('pmb_exam_sessions')->restrictOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->cascadeOnDelete();
            $table->string('participant_number', 40);
            $table->string('attendance_status', 20)->default('registered');
            $table->timestampTz('checked_in_at')->nullable();
            $table->foreignUlid('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('reschedule_count')->default(0);
            $table->foreignUlid('card_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestampsTz();
            $table->unique(['schedule_id', 'application_id']);
            $table->unique(['university_id', 'participant_number']);
            $table->index('session_id');
        });

        // Tambahan implementasi: permintaan jadwal ulang (11 §A.10).
        Schema::create('pmb_exam_reschedule_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('participant_id')->constrained('pmb_exam_participants')->cascadeOnDelete();
            $table->text('reason');
            $table->foreignUlid('attachment_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->foreignUlid('target_session_id')->nullable()->constrained('pmb_exam_sessions')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();
        });

        // Tambahan implementasi: CBT internal PMB (11 §B.5 opsi internal) —
        // tabel ujian PMB sendiri agar tidak meregresi `exams` akademik (KD-07).
        Schema::create('pmb_cbt_questions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('schedule_id')->constrained('pmb_exam_schedules')->cascadeOnDelete();
            $table->text('question');
            $table->json('options');
            $table->string('correct_option', 10);
            $table->decimal('points', 6, 2)->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('pmb_cbt_attempts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('participant_id')->unique()->constrained('pmb_exam_participants')->cascadeOnDelete();
            $table->json('question_order');
            $table->json('answers')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('deadline_at');
            $table->timestampTz('submitted_at')->nullable();
            $table->decimal('raw_score', 9, 4)->nullable();
            $table->unsignedInteger('violations')->default(0);
            $table->timestampsTz();
        });

        Schema::create('pmb_selection_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('wave_path_id')->constrained('pmb_wave_paths')->restrictOnDelete();
            $table->foreignUlid('selection_scheme_id')->constrained('pmb_selection_schemes')->restrictOnDelete();
            $table->string('run_type', 20)->default('regular');
            $table->json('scope_application_ids')->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('quota_snapshot')->nullable();
            $table->json('summary')->nullable();
            $table->char('result_hash', 64)->nullable();
            $table->boolean('has_unresolved_ties')->default(false);
            $table->timestampTz('computed_at')->nullable();
            $table->ulid('approval_request_id')->nullable()->index();
            $table->foreignUlid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->text('review_note')->nullable();
            $table->ulid('superseded_by_id')->nullable();
            $table->ulid('correction_request_id')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['university_id', 'wave_path_id', 'status']);
        });

        Schema::create('pmb_selection_scores', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->cascadeOnDelete();
            $table->foreignUlid('selection_component_id')->constrained('pmb_selection_components')->restrictOnDelete();
            $table->foreignUlid('examiner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('raw_score', 9, 4)->nullable();
            $table->boolean('passed')->nullable();
            $table->json('rubric_scores')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('source', 20);
            $table->foreignUlid('exam_session_id')->nullable()->constrained('pmb_exam_sessions')->nullOnDelete();
            $table->timestampTz('locked_at')->nullable();
            $table->foreignUlid('locked_by_run_id')->nullable()->constrained('pmb_selection_runs')->nullOnDelete();
            $table->foreignUlid('entered_by')->nullable()->constrained('users')->nullOnDelete();
            // NULL pada examiner_user_id tidak bentrok di unique; diisi model
            // agar komponen tanpa penguji tetap satu baris per aplikasi.
            $table->string('unique_key', 120);
            $table->timestampsTz();
            $table->unique('unique_key');
            $table->index(['university_id', 'selection_component_id']);
        });

        Schema::create('pmb_score_revisions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('selection_score_id')->constrained('pmb_selection_scores')->cascadeOnDelete();
            $table->decimal('old_raw_score', 9, 4)->nullable();
            $table->decimal('new_raw_score', 9, 4)->nullable();
            $table->boolean('old_passed')->nullable();
            $table->boolean('new_passed')->nullable();
            $table->text('reason');
            $table->string('source', 20);
            $table->ulid('approval_request_id')->nullable();
            $table->foreignUlid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->nullable();
        });

        // Tambahan implementasi: impor nilai dua tahap validasi → commit (FR-EXM-07).
        Schema::create('pmb_score_imports', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('schedule_id')->constrained('pmb_exam_schedules')->cascadeOnDelete();
            $table->foreignUlid('selection_component_id')->constrained('pmb_selection_components')->restrictOnDelete();
            $table->foreignUlid('file_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->json('valid_rows');
            $table->json('errors');
            $table->char('fingerprint', 64);
            $table->string('status', 20)->default('validated');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('committed_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('pmb_selection_run_results', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('selection_run_id')->constrained('pmb_selection_runs')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->restrictOnDelete();
            $table->boolean('eligible');
            $table->string('ineligible_reason', 40)->nullable();
            $table->json('component_scores');
            $table->decimal('final_score', 9, 4)->nullable();
            $table->decimal('final_score_rounded', 6, 2)->nullable();
            $table->unsignedInteger('rank_global')->nullable();
            $table->string('recommendation', 20);
            $table->string('fail_reason', 40)->nullable();
            $table->foreignUlid('assigned_choice_id')->nullable()->constrained('pmb_application_choices')->nullOnDelete();
            $table->foreignUlid('assigned_quota_id')->nullable()->constrained('pmb_quotas')->nullOnDelete();
            $table->json('waitlist_entries')->nullable();
            $table->json('tie_break_values')->nullable();
            $table->boolean('tie_unresolved')->default(false);
            $table->string('override_recommendation', 20)->nullable();
            $table->ulid('override_quota_id')->nullable();
            $table->text('override_reason')->nullable();
            $table->foreignUlid('override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->nullable();
            $table->unique(['selection_run_id', 'application_id']);
            $table->index(['selection_run_id', 'assigned_quota_id'], 'pmb_run_results_run_quota_index');
        });

        Schema::create('pmb_announcement_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('selection_run_id')->constrained('pmb_selection_runs')->restrictOnDelete();
            $table->timestampTz('scheduled_publish_at');
            $table->string('status', 20)->default('scheduled');
            $table->timestampTz('published_at')->nullable();
            $table->unsignedInteger('decisions_count')->default(0);
            $table->text('failure_reason')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'scheduled_publish_at']);
        });

        Schema::create('pmb_admission_decisions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->restrictOnDelete();
            $table->unsignedSmallInteger('decision_version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('decision', 20);
            $table->string('source', 30);
            $table->foreignUlid('selection_run_id')->nullable()->constrained('pmb_selection_runs')->nullOnDelete();
            $table->foreignUlid('run_result_id')->nullable()->constrained('pmb_selection_run_results')->nullOnDelete();
            $table->foreignUlid('quota_id')->nullable()->constrained('pmb_quotas')->nullOnDelete();
            $table->foreignUlid('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->foreignUlid('study_system_id')->nullable()->constrained('pmb_study_systems')->nullOnDelete();
            $table->unsignedInteger('waitlist_rank')->nullable();
            $table->json('waitlist_entries')->nullable();
            $table->string('publication_status', 20)->default('unpublished');
            $table->timestampTz('published_at')->nullable();
            $table->foreignUlid('announcement_batch_id')->nullable()->constrained('pmb_announcement_batches')->nullOnDelete();
            $table->text('correction_note')->nullable();
            $table->json('scholarship_award')->nullable();
            $table->ulid('previous_decision_id')->nullable();
            $table->ulid('current_key')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['application_id', 'decision_version']);
            $table->unique('current_key');
            $table->index(['university_id', 'decision', 'publication_status'], 'pmb_decisions_status_idx');
            $table->index(['quota_id', 'decision']);
        });

        Schema::create('pmb_acceptance_letters', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('decision_id')->unique()->constrained('pmb_admission_decisions')->restrictOnDelete();
            $table->string('letter_number', 50);
            $table->string('verification_code', 32)->unique();
            $table->foreignUlid('file_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoke_reason', 255)->nullable();
            $table->string('signature_reference', 100)->nullable();
            $table->timestampsTz();
            $table->unique(['university_id', 'letter_number']);
        });

        Schema::create('pmb_re_registrations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->unique()->constrained('pmb_applications')->restrictOnDelete();
            $table->foreignUlid('decision_id')->constrained('pmb_admission_decisions')->restrictOnDelete();
            $table->string('status', 30)->default('awaiting_confirmation');
            $table->timestampTz('deadline_at');
            $table->timestampTz('extended_until')->nullable();
            $table->string('extension_reason', 255)->nullable();
            $table->foreignUlid('extended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('revision_targets')->nullable();
            $table->timestampTz('revision_due_at')->nullable();
            $table->json('declarations')->nullable();
            $table->string('almamater_size', 10)->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('enrolled_at')->nullable();
            $table->timestampTz('declined_at')->nullable();
            $table->timestampTz('expired_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('decline_reason', 255)->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestampsTz();
            $table->index(['university_id', 'status', 'deadline_at']);
        });

        Schema::create('pmb_re_registration_checks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('re_registration_id')->constrained('pmb_re_registrations')->cascadeOnDelete();
            $table->string('check_code', 40);
            $table->string('evaluation', 10);
            $table->string('status', 20)->default('pending');
            $table->string('note', 255)->nullable();
            $table->foreignUlid('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('checked_at')->nullable();
            $table->timestampsTz();
            $table->unique(['re_registration_id', 'check_code']);
        });

        Schema::create('pmb_sequences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('scope', 30);
            $table->string('key', 100);
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestampsTz();
            $table->unique(['university_id', 'scope', 'key']);
        });

        Schema::create('pmb_student_conversions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->unique()->constrained('pmb_applications')->restrictOnDelete();
            $table->foreignUlid('re_registration_id')->constrained('pmb_re_registrations')->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('nim', 30)->nullable();
            $table->foreignUlid('student_id')->nullable()->unique()->constrained('students')->restrictOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('steps_completed');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('locked_until')->nullable();
            $table->timestampTz('next_attempt_at')->nullable();
            $table->string('last_error_code', 50)->nullable();
            $table->text('last_error_message')->nullable();
            $table->string('email_override', 191)->nullable();
            $table->timestampTz('succeeded_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        foreach ([
            'pmb_student_conversions', 'pmb_sequences', 'pmb_re_registration_checks', 'pmb_re_registrations',
            'pmb_acceptance_letters', 'pmb_admission_decisions', 'pmb_announcement_batches', 'pmb_selection_run_results',
            'pmb_score_imports', 'pmb_score_revisions', 'pmb_selection_scores', 'pmb_selection_runs', 'pmb_cbt_attempts',
            'pmb_cbt_questions', 'pmb_exam_reschedule_requests', 'pmb_exam_participants', 'pmb_exam_session_examiners',
            'pmb_exam_sessions', 'pmb_exam_schedules', 'pmb_exam_rooms', 'pmb_exam_venues',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
