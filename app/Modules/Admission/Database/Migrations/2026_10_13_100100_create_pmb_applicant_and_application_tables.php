<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PMB — akun & profil pendaftar, aplikasi, dokumen, verifikasi
 * (docs/pmb/10 §4.2–4.4, langkah 4–6 urutan migrasi).
 *
 * Kolom `active_key` / `current_key` diisi model (bukan generated column)
 * agar perilaku sama di MySQL & SQLite (test); unique index memanfaatkan
 * perilaku NULL tidak bentrok (10 §5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pmb_applicant_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('email', 191);
            $table->string('phone', 20)->nullable();
            $table->string('password');
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('phone_verified_at')->nullable();
            $table->string('status', 20)->default('unverified');
            $table->unsignedSmallInteger('failed_login_count')->default(0);
            $table->unsignedTinyInteger('lockout_count')->default(0);
            $table->timestampTz('locked_until')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->string('info_source', 30)->nullable();
            $table->foreignUlid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('crm_lead_reference', 100)->nullable();
            $table->string('suspension_reason', 255)->nullable();
            $table->timestampTz('deactivated_at')->nullable();
            $table->timestampTz('anonymized_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestampsTz();
            $table->unique(['university_id', 'email']);
            $table->unique(['university_id', 'phone']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('pmb_otp_challenges', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->nullable()->constrained('pmb_applicant_accounts')->cascadeOnDelete();
            $table->string('channel', 10);
            $table->string('destination_hash', 64);
            $table->string('purpose', 30);
            $table->string('code_hash', 255);
            $table->json('payload')->nullable();
            $table->timestampTz('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();
            $table->index(['destination_hash', 'purpose', 'created_at']);
        });

        Schema::create('pmb_applicant_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->unique()->constrained('pmb_applicant_accounts')->cascadeOnDelete();
            $table->string('full_name', 150)->nullable();
            $table->char('nationality', 2)->nullable()->default('ID');
            $table->text('nik')->nullable();
            $table->text('passport_number')->nullable();
            $table->char('passport_country', 2)->nullable();
            $table->char('identity_hash', 64)->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('religion', 20)->nullable();
            $table->text('mother_name')->nullable();
            $table->string('address_line', 255)->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('region_code', 20)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->text('special_needs')->nullable();
            $table->text('special_needs_note')->nullable();
            $table->text('pddikti_extra')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestampsTz();
            $table->unique(['university_id', 'identity_hash']);
        });

        Schema::create('pmb_applicant_guardians', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->constrained('pmb_applicant_accounts')->cascadeOnDelete();
            $table->string('relationship', 20);
            $table->string('full_name', 150);
            $table->string('phone', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('nik')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('education_level', 30)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('income_range', 30)->nullable();
            $table->boolean('is_deceased')->default(false);
            $table->timestampsTz();
            $table->index(['applicant_account_id', 'relationship']);
        });

        Schema::create('pmb_applicant_educations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->constrained('pmb_applicant_accounts')->cascadeOnDelete();
            $table->string('level', 20);
            $table->string('institution_name', 200);
            $table->string('institution_npsn', 8)->nullable();
            $table->string('school_type', 20)->nullable();
            $table->string('major', 100)->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->boolean('is_graduating')->default(false);
            $table->string('nisn', 10)->nullable();
            $table->string('previous_student_id', 30)->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->unsignedSmallInteger('credits_earned')->nullable();
            $table->string('previous_accreditation', 30)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->timestampsTz();
        });

        Schema::create('pmb_report_scores', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_education_id')->constrained('pmb_applicant_educations')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester');
            $table->string('subject_code', 30);
            $table->string('subject_name', 100);
            $table->decimal('score', 5, 2);
            $table->timestampsTz();
            $table->unique(['applicant_education_id', 'semester', 'subject_code'], 'pmb_report_scores_unique');
        });

        Schema::create('pmb_applications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->constrained('pmb_applicant_accounts')->restrictOnDelete();
            $table->foreignUlid('period_id')->constrained('pmb_periods')->restrictOnDelete();
            $table->foreignUlid('wave_id')->constrained('pmb_waves')->restrictOnDelete();
            $table->foreignUlid('wave_path_id')->constrained('pmb_wave_paths')->restrictOnDelete();
            $table->foreignUlid('admission_path_id')->constrained('pmb_admission_paths')->restrictOnDelete();
            $table->foreignUlid('form_template_id')->constrained('pmb_form_templates')->restrictOnDelete();
            $table->string('registration_number', 40)->nullable();
            $table->string('form_status', 30)->default('draft');
            $table->string('verification_status', 30)->default('not_started');
            $table->string('selection_status', 30)->default('not_eligible');
            $table->string('registration_payment_status', 30)->default('not_required');
            $table->foreignUlid('primary_education_id')->nullable()->constrained('pmb_applicant_educations')->nullOnDelete();
            $table->json('config_snapshot');
            $table->text('profile_snapshot')->nullable();
            $table->json('declarations')->nullable();
            $table->json('revision_targets')->nullable();
            $table->timestampTz('revision_due_at')->nullable();
            $table->string('info_source', 30)->nullable();
            $table->string('info_source_detail', 255)->nullable();
            $table->string('referral_code', 50)->nullable();
            $table->text('kip_number')->nullable();
            $table->string('kip_registration_number', 50)->nullable();
            $table->string('kip_status', 30)->nullable();
            $table->boolean('scholarship_interest')->default(false);
            $table->string('scholarship_program_code', 50)->nullable();
            $table->string('rejection_reason_code', 50)->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('first_submitted_at')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->string('withdraw_reason', 255)->nullable();
            $table->string('active_key', 80)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignUlid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'registration_number']);
            $table->unique(['applicant_account_id', 'active_key']);
            $table->index(['university_id', 'wave_path_id', 'verification_status'], 'pmb_app_queue_idx');
            $table->index(['university_id', 'period_id', 'form_status'], 'pmb_app_period_idx');
            $table->index(['university_id', 'selection_status']);
            $table->index('submitted_at');
        });

        Schema::create('pmb_account_consents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->constrained('pmb_applicant_accounts')->cascadeOnDelete();
            $table->foreignUlid('application_id')->nullable()->constrained('pmb_applications')->cascadeOnDelete();
            $table->string('consent_type', 40);
            $table->foreignUlid('content_id')->nullable()->constrained('pmb_contents')->nullOnDelete();
            $table->unsignedInteger('document_version')->nullable();
            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestampTz('created_at')->nullable();
            $table->index(['applicant_account_id', 'consent_type']);
        });

        Schema::create('pmb_application_choices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->cascadeOnDelete();
            $table->unsignedTinyInteger('priority');
            $table->foreignUlid('quota_id')->constrained('pmb_quotas')->restrictOnDelete();
            $table->foreignUlid('study_program_id')->constrained('study_programs')->restrictOnDelete();
            $table->foreignUlid('study_system_id')->constrained('pmb_study_systems')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['application_id', 'priority']);
            $table->unique(['application_id', 'quota_id']);
            $table->index(['university_id', 'study_program_id']);
        });

        Schema::create('pmb_application_answers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->cascadeOnDelete();
            $table->foreignUlid('form_field_id')->constrained('pmb_form_fields')->restrictOnDelete();
            $table->text('value_text')->nullable();
            $table->json('value_json')->nullable();
            $table->timestampsTz();
            $table->unique(['application_id', 'form_field_id']);
        });

        Schema::create('pmb_application_status_histories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->cascadeOnDelete();
            $table->string('lifecycle', 30);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('actor_type', 20);
            $table->ulid('actor_id')->nullable();
            $table->string('reason_code', 50)->nullable();
            $table->text('reason_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->nullable();
            $table->index(['application_id', 'lifecycle', 'created_at'], 'pmb_hist_app_idx');
            $table->index(['university_id', 'lifecycle', 'to_status', 'created_at'], 'pmb_hist_tenant_idx');
        });

        Schema::create('pmb_duplicate_flags', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->constrained('pmb_applicant_accounts')->cascadeOnDelete();
            $table->foreignUlid('related_account_id')->nullable()->constrained('pmb_applicant_accounts')->nullOnDelete();
            $table->foreignUlid('application_id')->nullable()->constrained('pmb_applications')->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('similarity_score', 5, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->text('note')->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignUlid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'status']);
        });

        Schema::create('pmb_application_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->cascadeOnDelete();
            $table->foreignUlid('document_type_id')->constrained('pmb_document_types')->restrictOnDelete();
            $table->string('stage', 20);
            $table->unsignedTinyInteger('slot')->default(1);
            $table->boolean('is_mandatory');
            $table->string('status', 30)->default('missing');
            // Tanpa FK keras: siklus dengan pmb_document_versions (10 §9 langkah 6).
            $table->ulid('current_version_id')->nullable()->index();
            $table->timestampTz('locked_at')->nullable();
            $table->timestampsTz();
            $table->unique(['application_id', 'document_type_id', 'stage', 'slot'], 'pmb_app_docs_unique');
            $table->index(['university_id', 'status']);
        });

        Schema::create('pmb_document_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_document_id')->constrained('pmb_application_documents')->cascadeOnDelete();
            $table->unsignedSmallInteger('version_no');
            $table->foreignUlid('file_upload_id')->constrained('file_uploads')->restrictOnDelete();
            $table->string('uploaded_by_type', 20);
            $table->ulid('uploaded_by_id');
            $table->char('checksum', 64);
            $table->timestampTz('created_at')->nullable();
            $table->unique(['application_document_id', 'version_no'], 'pmb_doc_versions_unique');
            $table->index('checksum');
        });

        Schema::create('pmb_applicant_achievements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->constrained('pmb_applicant_accounts')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('category', 20);
            $table->string('level', 20);
            $table->string('rank', 20);
            $table->unsignedSmallInteger('year');
            $table->string('organizer', 200)->nullable();
            $table->foreignUlid('application_document_id')->nullable()->constrained('pmb_application_documents')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('pmb_document_reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('document_version_id')->constrained('pmb_document_versions')->cascadeOnDelete();
            $table->string('decision', 30);
            $table->string('reason_code', 40)->nullable();
            $table->text('note_for_applicant')->nullable();
            $table->text('internal_note')->nullable();
            $table->foreignUlid('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('pmb_verification_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->cascadeOnDelete();
            $table->foreignUlid('reviewer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 10);
            $table->string('status', 20)->default('active');
            $table->timestampTz('claimed_until')->nullable();
            $table->ulid('active_key')->nullable();
            $table->timestampsTz();
            $table->unique('active_key');
            $table->index(['reviewer_user_id', 'status']);
        });

        Schema::create('pmb_review_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->cascadeOnDelete();
            $table->string('context', 30);
            $table->string('visibility', 20);
            $table->text('body');
            $table->json('target_fields')->nullable();
            $table->timestampTz('due_at')->nullable();
            // Null = catatan sistem (mis. job kedaluwarsa revisi).
            $table->foreignUlid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        // Tambahan implementasi (bukan di 10 §1): deklarasi konflik kepentingan
        // petugas/penguji (05 §G.4 butir 5, 06 FR-EXM-04).
        Schema::create('pmb_conflict_declarations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->cascadeOnDelete();
            $table->string('reason', 255);
            $table->timestampsTz();
            $table->unique(['user_id', 'application_id']);
        });

        // Tambahan implementasi: permintaan pemulihan akun (11 §A.3) — tidak
        // memiliki tabel di 10 §4.
        Schema::create('pmb_account_recovery_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('applicant_account_id')->nullable()->constrained('pmb_applicant_accounts')->nullOnDelete();
            $table->string('full_name', 150);
            $table->date('birth_date');
            $table->char('identity_hash', 64);
            $table->string('new_email', 191);
            $table->foreignUlid('identity_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->text('resolution_note')->nullable();
            $table->foreignUlid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach ([
            'pmb_account_recovery_requests', 'pmb_conflict_declarations', 'pmb_review_notes', 'pmb_verification_assignments',
            'pmb_document_reviews', 'pmb_applicant_achievements', 'pmb_document_versions', 'pmb_application_documents',
            'pmb_duplicate_flags', 'pmb_application_status_histories', 'pmb_application_answers', 'pmb_application_choices',
            'pmb_account_consents', 'pmb_applications', 'pmb_report_scores', 'pmb_applicant_educations',
            'pmb_applicant_guardians', 'pmb_applicant_profiles', 'pmb_otp_challenges', 'pmb_applicant_accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
