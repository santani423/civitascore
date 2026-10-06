<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HumanResource\Enums\PositionType;
use Modules\HumanResource\Enums\WorkUnitType;

/**
 * Master data SDM yang sebelumnya tidak ada sama sekali — `employees`
 * hanya punya `unit_kerja`/`position` berupa teks bebas. Fakultas & prodi
 * TIDAK diduplikasi di sini: unit bertipe faculty/study_program merujuk
 * tabel akademik yang sudah ada lewat faculty_id/study_program_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_units', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->foreignUlid('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignUlid('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->enum('type', array_column(WorkUnitType::cases(), 'value'));
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'parent_id']);
        });

        Schema::create('positions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->enum('type', array_column(PositionType::cases(), 'value'));
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['university_id', 'code']);
        });

        Schema::create('ranks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('name');
            $table->string('grade', 20);
            $table->unsignedSmallInteger('level')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['university_id', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranks');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('work_units');
    }
};
