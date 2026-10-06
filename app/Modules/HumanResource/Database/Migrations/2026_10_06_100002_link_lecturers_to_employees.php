<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\LecturerStatus;

/**
 * Dosen tetap tinggal di `lecturers` (dipakai Modul Akademik, Skripsi,
 * Magang) — tidak dibuat tabel dosen baru. Setiap dosen dihubungkan 1:1 ke
 * baris `employees` (employee_type = lecturer) yang menyimpan data
 * kepegawaiannya; atribut khusus dosen (NIDK, serdos, jabatan akademik,
 * homebase) ditambahkan di sini.
 *
 * Backfill: setiap dosen yang sudah ada dibuatkan baris pegawai, supaya
 * data lama langsung muncul di Modul SDM tanpa input ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            $table->foreignUlid('employee_id')->nullable()->after('faculty_id')->unique()->constrained('employees')->nullOnDelete();
            $table->foreignUlid('study_program_id')->nullable()->after('employee_id')->constrained('study_programs')->nullOnDelete();
            $table->string('nidk', 50)->nullable()->after('nidn');
            $table->string('serdos_number', 50)->nullable();
            $table->enum('academic_rank', array_column(AcademicRank::cases(), 'value'))->nullable();
            $table->string('expertise')->nullable();
            $table->enum('lecturer_status', array_column(LecturerStatus::cases(), 'value'))->nullable();
            $table->date('teaching_started_at')->nullable();
        });

        $this->backfillEmployees();
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            $table->dropForeign(['employee_id']);
            $table->dropUnique(['employee_id']);
            $table->dropColumn('employee_id');
            $table->dropConstrainedForeignId('study_program_id');
            $table->dropColumn(['nidk', 'serdos_number', 'academic_rank', 'expertise', 'lecturer_status', 'teaching_started_at']);
        });
    }

    private function backfillEmployees(): void
    {
        $facultyNames = DB::table('faculties')->pluck('name', 'id');

        // chunkById, bukan chunk(): baris yang sudah diupdate keluar dari
        // filter whereNull, jadi offset-based chunk() akan melompati data.
        DB::table('lecturers')->whereNull('employee_id')->chunkById(500, function ($lecturers) use ($facultyNames): void {
            foreach ($lecturers as $lecturer) {
                $employeeId = (string) Str::ulid();

                DB::table('employees')->insert([
                    'id' => $employeeId,
                    'university_id' => $lecturer->university_id,
                    'employee_type' => 'lecturer',
                    'unit_kerja' => $facultyNames[$lecturer->faculty_id] ?? 'Dosen',
                    'name' => $lecturer->name,
                    'email' => $lecturer->email,
                    'position' => 'Dosen',
                    'faculty_id' => $lecturer->faculty_id,
                    'employment_status' => 'permanent',
                    'is_active' => $lecturer->is_active,
                    'created_at' => $lecturer->created_at,
                    'updated_at' => now(),
                ]);

                DB::table('lecturers')->where('id', $lecturer->id)->update([
                    'employee_id' => $employeeId,
                    'lecturer_status' => LecturerStatus::Permanent->value,
                ]);
            }
        });
    }
};
