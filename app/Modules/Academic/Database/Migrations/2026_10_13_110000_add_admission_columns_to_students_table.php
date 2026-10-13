<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak asal PMB pada `students` (docs/pmb/10 §6, BR-37) + tabel
 * `student_profiles` 1:1 untuk data identitas PDDikti (GAP-04; rekomendasi
 * 10 §6 agar `students` tetap ramping). Additive & nullable — tanpa FK keras
 * ke pmb_applications agar modul Academic tidak bergantung pada Admission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->ulid('admission_application_id')->nullable()->unique()->after('user_id');
            $table->string('admission_path_code', 30)->nullable()->after('admission_year');
            $table->string('study_system_code', 20)->nullable()->after('admission_path_code');
        });

        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->text('nik')->nullable();
            $table->char('nationality', 2)->nullable();
            $table->text('passport_number')->nullable();
            $table->string('religion', 20)->nullable();
            $table->text('mother_name')->nullable();
            $table->string('father_name', 150)->nullable();
            $table->string('previous_school', 200)->nullable();
            $table->string('previous_school_npsn', 8)->nullable();
            $table->string('nisn', 10)->nullable();
            $table->string('region_code', 20)->nullable();
            $table->string('address_line', 255)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->text('pddikti_extra')->nullable();
            $table->json('guardians')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');

        // Rollback hanya bila kolom masih kosong (10 §9 Rollback).
        if (DB::table('students')->whereNotNull('admission_application_id')->exists()) {
            throw new RuntimeException('students.admission_application_id sudah berisi data; rollback dibatalkan.');
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique(['admission_application_id']);
            $table->dropColumn(['admission_application_id', 'admission_path_code', 'study_system_code']);
        });
    }
};
