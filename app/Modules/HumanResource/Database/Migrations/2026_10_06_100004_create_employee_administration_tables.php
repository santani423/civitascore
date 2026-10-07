<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HumanResource\Enums\ContractStatus;
use Modules\HumanResource\Enums\ContractType;
use Modules\HumanResource\Enums\DocumentStatus;
use Modules\HumanResource\Enums\DocumentType;
use Modules\HumanResource\Enums\PerformanceCategory;
use Modules\HumanResource\Enums\PerformanceStatus;
use Modules\HumanResource\Enums\TrainingStatus;
use Modules\HumanResource\Enums\TransferStatus;

/**
 * Kontrak, dokumen, pengembangan (pelatihan/sertifikasi), kinerja, dan
 * mutasi. Berkas tidak disimpan di tabel ini — selalu lewat `file_uploads`
 * (Modul FileManagement, disk privat) dan dirujuk lewat FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('contract_number', 100);
            $table->enum('contract_type', array_column(ContractType::cases(), 'value'));
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', array_column(ContractStatus::cases(), 'value'))->default(ContractStatus::Active->value);
            $table->foreignUlid('document_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->text('notes')->nullable();
            // Ambang pengingat terakhir yang sudah dikirim (90/60/30/7) —
            // membuat command pengingat harian idempotent.
            $table->unsignedSmallInteger('last_reminder_days')->nullable();
            $table->timestampTz('terminated_at')->nullable();
            $table->timestampsTz();

            $table->unique(['university_id', 'contract_number']);
            $table->index(['university_id', 'status', 'end_date']);
            $table->index('employee_id');
        });

        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('document_type', array_column(DocumentType::cases(), 'value'));
            $table->string('title')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->foreignUlid('file_upload_id')->constrained('file_uploads')->restrictOnDelete();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->enum('status', array_column(DocumentStatus::cases(), 'value'))->default(DocumentStatus::Pending->value);
            $table->text('verification_note')->nullable();
            $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->foreignUlid('previous_version_id')->nullable()->constrained('employee_documents')->nullOnDelete();
            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['employee_id', 'document_type', 'is_current']);
            $table->index(['university_id', 'expires_at']);
        });

        Schema::create('employee_trainings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('organizer');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('duration_hours', 6, 1)->nullable();
            $table->string('location')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->enum('status', array_column(TrainingStatus::cases(), 'value'))->default(TrainingStatus::Completed->value);
            $table->foreignUlid('certificate_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'start_date']);
            $table->index('employee_id');
        });

        Schema::create('employee_certifications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('issuer');
            $table->string('certificate_number', 100)->nullable();
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->foreignUlid('document_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'expires_at']);
            $table->index('employee_id');
        });

        Schema::create('performance_reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUlid('reviewer_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('period', 50);
            $table->decimal('score', 5, 2);
            $table->enum('category', array_column(PerformanceCategory::cases(), 'value'));
            $table->text('notes')->nullable();
            $table->enum('status', array_column(PerformanceStatus::cases(), 'value'))->default(PerformanceStatus::Draft->value);
            $table->timestampsTz();

            $table->unique(['employee_id', 'period']);
            $table->index(['university_id', 'period']);
        });

        Schema::create('employee_transfers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUlid('from_work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->string('from_work_unit_name')->nullable();
            $table->foreignUlid('to_work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->string('to_work_unit_name')->nullable();
            $table->foreignUlid('from_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('from_position_name')->nullable();
            $table->foreignUlid('to_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('to_position_name')->nullable();
            $table->date('effective_date');
            $table->string('decree_number', 100)->nullable();
            $table->foreignUlid('document_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->enum('status', array_column(TransferStatus::cases(), 'value'))->default(TransferStatus::Scheduled->value);
            $table->timestampTz('applied_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['university_id', 'status', 'effective_date']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_transfers');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('employee_certifications');
        Schema::dropIfExists('employee_trainings');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employee_contracts');
    }
};
