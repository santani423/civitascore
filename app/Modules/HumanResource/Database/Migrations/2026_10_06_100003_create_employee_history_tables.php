<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Enums\LecturerActivityType;
use Modules\HumanResource\Enums\PositionType;

/**
 * Riwayat pegawai. Setiap baris riwayat menyimpan snapshot nama
 * (position_name, work_unit_name, rank_name, grade) selain FK ke master —
 * supaya riwayat tetap utuh walau master jabatan/unit/pangkat kemudian
 * diganti namanya atau dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_educations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('level', array_column(EducationLevel::cases(), 'value'));
            $table->string('institution');
            $table->string('major')->nullable();
            $table->unsignedSmallInteger('entry_year')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('certificate_number', 100)->nullable();
            $table->decimal('gpa', 4, 2)->nullable();
            $table->foreignUlid('document_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['employee_id', 'level']);
        });

        Schema::create('employee_positions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUlid('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('position_name');
            $table->enum('position_type', array_column(PositionType::cases(), 'value'));
            $table->foreignUlid('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->string('work_unit_name')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('decree_number', 100)->nullable();
            $table->foreignUlid('decree_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestampsTz();

            $table->index(['employee_id', 'is_current']);
        });

        Schema::create('employee_ranks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUlid('rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->string('rank_name');
            $table->string('grade', 20);
            $table->string('decree_number', 100)->nullable();
            $table->date('decree_date')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignUlid('decree_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->boolean('is_current')->default(true);
            $table->timestampsTz();

            $table->index(['employee_id', 'is_current']);
        });

        Schema::create('lecturer_academic_ranks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('academic_rank', array_column(AcademicRank::cases(), 'value'));
            $table->decimal('credit_points', 8, 2)->nullable();
            $table->string('decree_number', 100)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignUlid('decree_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->boolean('is_current')->default(true);
            $table->timestampsTz();

            $table->index(['employee_id', 'is_current']);
        });

        Schema::create('lecturer_activities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', array_column(LecturerActivityType::cases(), 'value'));
            $table->string('title');
            $table->string('role', 100)->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('funding_source')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->foreignUlid('document_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['employee_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_activities');
        Schema::dropIfExists('lecturer_academic_ranks');
        Schema::dropIfExists('employee_ranks');
        Schema::dropIfExists('employee_positions');
        Schema::dropIfExists('employee_educations');
    }
};
