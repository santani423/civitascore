<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PMB — domain konfigurasi (docs/pmb/10 §4.1, langkah 1–3 urutan migrasi §9).
 * Semua status disimpan sebagai string + PHP backed enum (KD-10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pmb_admission_paths', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('category', 30);
            $table->text('description')->nullable();
            $table->json('eligible_degree_levels');
            $table->boolean('requires_exam')->default(false);
            $table->boolean('requires_previous_institution')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('pmb_study_systems', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->string('schedule_note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('pmb_document_types', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->json('allowed_mime_types');
            $table->unsignedInteger('max_size_kb')->default(2048);
            $table->boolean('allows_multiple')->default(false);
            $table->boolean('requires_original_check')->default(false);
            $table->boolean('is_sensitive')->default(false);
            $table->string('retention_class', 30)->default('applicant');
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('pmb_periods', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('entry_academic_term_id')->constrained('academic_terms')->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 30)->default('draft');
            $table->text('description')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('pmb_waves', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('period_id')->constrained('pmb_periods')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->timestampTz('registration_opens_at');
            $table->timestampTz('registration_closes_at');
            $table->timestampTz('payment_deadline_at');
            $table->timestampTz('form_lock_at');
            $table->date('exam_starts_on')->nullable();
            $table->date('exam_ends_on')->nullable();
            $table->timestampTz('announcement_at');
            $table->timestampTz('re_registration_opens_at');
            $table->timestampTz('re_registration_closes_at');
            $table->boolean('is_ods')->default(false);
            $table->string('status', 30)->default('draft');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['period_id', 'code']);
            $table->index(['university_id', 'status']);
            $table->index('registration_closes_at');
        });

        Schema::create('pmb_form_templates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->json('core_field_rules');
            $table->ulid('based_on_id')->nullable()->index();
            $table->timestampTz('published_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'code', 'version']);
        });

        Schema::create('pmb_form_fields', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('form_template_id')->constrained('pmb_form_templates')->cascadeOnDelete();
            $table->string('section', 40);
            $table->string('key', 60);
            $table->string('label', 200);
            $table->string('help_text', 500)->nullable();
            $table->string('placeholder', 500)->nullable();
            $table->string('type', 20);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('validation')->nullable();
            $table->json('visibility_rule')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_reportable')->default(false);
            $table->string('stage', 20)->default('application');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
            $table->unique(['form_template_id', 'key']);
        });

        Schema::create('pmb_selection_schemes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->string('allocation_method', 30)->default('score_priority');
            $table->json('tie_break_rules');
            $table->unsignedTinyInteger('rounding_scale')->default(2);
            $table->ulid('based_on_id')->nullable()->index();
            $table->timestampTz('locked_at')->nullable();
            $table->foreignUlid('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'code', 'version']);
        });

        Schema::create('pmb_selection_components', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('selection_scheme_id')->constrained('pmb_selection_schemes')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->string('source', 30);
            $table->string('score_type', 20)->default('numeric');
            $table->decimal('raw_min', 9, 4)->nullable();
            $table->decimal('raw_max', 9, 4)->nullable();
            $table->decimal('weight', 7, 4)->default(0);
            $table->decimal('minimum_normalized', 5, 2)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedTinyInteger('tie_break_priority')->nullable();
            $table->string('normalization', 20)->default('minmax');
            $table->json('rubric')->nullable();
            $table->json('config')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
            $table->unique(['selection_scheme_id', 'code']);
        });

        Schema::create('pmb_wave_paths', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('wave_id')->constrained('pmb_waves')->cascadeOnDelete();
            $table->foreignUlid('admission_path_id')->constrained('pmb_admission_paths')->restrictOnDelete();
            $table->decimal('registration_fee', 14, 2)->default(0);
            $table->string('payment_timing', 20)->default('before_submit');
            $table->unsignedTinyInteger('max_choices')->default(2);
            $table->boolean('allow_cross_system_choice')->default(false);
            $table->foreignUlid('form_template_id')->constrained('pmb_form_templates')->restrictOnDelete();
            $table->foreignUlid('selection_scheme_id')->nullable()->constrained('pmb_selection_schemes')->restrictOnDelete();
            $table->string('quota_mode', 20)->default('period');
            $table->boolean('bulk_verifiable')->default(false);
            $table->boolean('is_open')->default(true);
            $table->text('public_notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['wave_id', 'admission_path_id']);
        });

        Schema::create('pmb_quotas', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('period_id')->constrained('pmb_periods')->cascadeOnDelete();
            $table->foreignUlid('admission_path_id')->constrained('pmb_admission_paths')->restrictOnDelete();
            $table->foreignUlid('study_program_id')->constrained('study_programs')->restrictOnDelete();
            $table->foreignUlid('study_system_id')->constrained('pmb_study_systems')->restrictOnDelete();
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('waitlist_capacity')->default(0);
            $table->decimal('min_final_score', 5, 2)->nullable();
            $table->json('wave_caps')->nullable();
            $table->unsignedInteger('accepted_count')->default(0);
            $table->boolean('is_offered')->default(true);
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['period_id', 'admission_path_id', 'study_program_id', 'study_system_id'], 'pmb_quotas_unique_dim');
            $table->index(['university_id', 'study_program_id']);
        });

        Schema::create('pmb_document_requirements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('wave_path_id')->constrained('pmb_wave_paths')->cascadeOnDelete();
            $table->foreignUlid('document_type_id')->constrained('pmb_document_types')->restrictOnDelete();
            $table->string('stage', 20)->default('application');
            $table->boolean('is_mandatory')->default(true);
            $table->json('condition')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
            $table->unique(['wave_path_id', 'document_type_id', 'stage'], 'pmb_doc_req_unique');
        });

        Schema::create('pmb_fee_components', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('period_id')->constrained('pmb_periods')->cascadeOnDelete();
            $table->foreignUlid('study_program_id')->constrained('study_programs')->restrictOnDelete();
            $table->foreignUlid('study_system_id')->constrained('pmb_study_systems')->restrictOnDelete();
            $table->foreignUlid('admission_path_id')->nullable()->constrained('pmb_admission_paths')->restrictOnDelete();
            $table->string('component_code', 30);
            $table->string('name', 150);
            $table->decimal('amount', 14, 2);
            $table->boolean('is_mandatory_for_enrollment')->default(true);
            $table->boolean('installable')->default(false);
            $table->json('installment_plan')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['period_id', 'study_program_id', 'study_system_id', 'admission_path_id', 'component_code'], 'pmb_fee_unique');
        });

        Schema::create('pmb_program_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('study_program_id')->unique()->constrained('study_programs')->cascadeOnDelete();
            $table->string('slug', 120);
            $table->string('summary', 500)->nullable();
            $table->longText('description')->nullable();
            $table->string('accreditation_label', 50)->nullable();
            $table->date('accreditation_valid_until')->nullable();
            $table->string('degree_title', 50)->nullable();
            $table->string('study_duration', 50)->nullable();
            $table->text('career_prospects')->nullable();
            $table->foreignUlid('cover_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->foreignUlid('brochure_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'slug']);
        });

        Schema::create('pmb_contents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('type', 30);
            $table->foreignUlid('period_id')->nullable()->constrained('pmb_periods')->nullOnDelete();
            $table->string('slug', 150)->nullable();
            $table->string('title', 250);
            $table->longText('body')->nullable();
            $table->string('category', 60)->nullable();
            $table->foreignUlid('file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->timestampTz('publish_at')->nullable();
            $table->timestampTz('unpublish_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['university_id', 'type', 'status']);
            $table->unique(['university_id', 'type', 'slug', 'version'], 'pmb_contents_slug_version_unique');
        });
    }

    public function down(): void
    {
        foreach ([
            'pmb_contents', 'pmb_program_profiles', 'pmb_fee_components', 'pmb_document_requirements', 'pmb_quotas',
            'pmb_wave_paths', 'pmb_selection_components', 'pmb_selection_schemes', 'pmb_form_fields',
            'pmb_form_templates', 'pmb_waves', 'pmb_periods', 'pmb_document_types', 'pmb_study_systems', 'pmb_admission_paths',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
