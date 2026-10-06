<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Enums\Gender;
use Modules\HumanResource\Enums\StaffCategory;

/**
 * `employees` (Modul Akademik) dijadikan master seluruh pegawai — dosen
 * maupun tenaga kependidikan — alih-alih membuat tabel pegawai baru.
 * Kolom lama `unit_kerja`/`position` (teks bebas) dipertahankan dan
 * disinkronkan dari master work_units/positions, supaya Dashboard dan
 * ReportService yang sudah membacanya tetap berjalan tanpa diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->foreignUlid('user_id')->nullable()->after('university_id')->unique()->constrained('users')->nullOnDelete();
            $table->enum('employee_type', array_column(EmployeeType::cases(), 'value'))->default(EmployeeType::Staff->value)->after('user_id');
            $table->string('nik', 32)->nullable();
            $table->string('nip', 50)->nullable();
            $table->enum('gender', array_column(Gender::cases(), 'value'))->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->enum('employment_status', array_column(EmploymentStatus::cases(), 'value'))->default(EmploymentStatus::Permanent->value);
            $table->foreignUlid('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            $table->foreignUlid('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignUlid('rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->foreignUlid('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignUlid('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->enum('highest_education', array_column(EducationLevel::cases(), 'value'))->nullable();
            $table->enum('staff_category', array_column(StaffCategory::cases(), 'value'))->nullable();
            $table->date('joined_at')->nullable();
            $table->string('inactive_reason')->nullable();
            $table->date('inactive_at')->nullable();
            $table->softDeletesTz();

            $table->unique(['university_id', 'nip']);
            $table->unique(['university_id', 'nik']);
            $table->index(['university_id', 'employee_type']);
            $table->index(['university_id', 'employment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique(['university_id', 'nip']);
            $table->dropUnique(['university_id', 'nik']);
            $table->dropIndex(['university_id', 'employee_type']);
            $table->dropIndex(['university_id', 'employment_status']);

            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');

            foreach (['work_unit_id', 'position_id', 'rank_id', 'faculty_id', 'study_program_id'] as $foreign) {
                $table->dropConstrainedForeignId($foreign);
            }

            $table->dropSoftDeletesTz();
            $table->dropColumn([
                'employee_type', 'nik', 'nip', 'gender', 'birth_place', 'birth_date', 'phone', 'address',
                'employment_status', 'highest_education', 'staff_category', 'joined_at', 'inactive_reason', 'inactive_at',
            ]);
        });
    }
};
