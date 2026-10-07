<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi data induk mahasiswa (RANCANGAN-APLIKASI.md §4.3) langsung di
 * `students` — bukan tabel profil terpisah: kontak & alamat yang boleh
 * diubah sendiri oleh mahasiswa, tempat lahir & jenis kelamin, foto
 * (berkas FileManagement), dan dosen wali (§4.19 "Penetapan dosen wali").
 *
 * `status` diganti dari enum ke string supaya status "Mengundurkan Diri"
 * (resigned) bisa ditambahkan tanpa ALTER enum — nilai sah tetap dijaga
 * cast StudentStatus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('status', 20)->change();

            $table->foreignUlid('academic_advisor_id')->nullable()->after('study_program_id')->constrained('lecturers')->nullOnDelete();
            $table->string('birth_place', 100)->nullable()->after('tanggal_lahir');
            $table->string('gender', 10)->nullable()->after('birth_place');
            $table->string('phone', 30)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->foreignUlid('photo_file_id')->nullable()->after('address')->constrained('file_uploads')->nullOnDelete();

            $table->index(['university_id', 'academic_advisor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropIndex(['university_id', 'academic_advisor_id']);
            $table->dropConstrainedForeignId('academic_advisor_id');
            $table->dropConstrainedForeignId('photo_file_id');
            $table->dropColumn(['birth_place', 'gender', 'phone', 'address']);
            $table->enum('status', ['active', 'leave', 'graduated', 'inactive', 'dropped_out'])->change();
        });
    }
};
