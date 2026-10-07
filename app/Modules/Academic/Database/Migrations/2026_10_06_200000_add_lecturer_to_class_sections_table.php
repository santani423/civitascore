<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dosen pengampu (penanggung jawab) kelas. Satu kolom FK — bukan tabel
 * pivot baru — karena seluruh alur yang membutuhkannya (jadwal mahasiswa,
 * materi/tugas milik kelas, ujian) cukup dengan satu dosen penanggung
 * jawab per kelas. Nullable: kelas lama hasil seeder belum punya dosen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sections', function (Blueprint $table): void {
            $table->foreignUlid('lecturer_id')->nullable()->after('course_id')->constrained('lecturers')->nullOnDelete();
            $table->index(['university_id', 'lecturer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('class_sections', function (Blueprint $table): void {
            $table->dropIndex(['university_id', 'lecturer_id']);
            $table->dropConstrainedForeignId('lecturer_id');
        });
    }
};
