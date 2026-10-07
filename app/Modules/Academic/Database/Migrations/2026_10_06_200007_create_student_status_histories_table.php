<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat perubahan status akademik mahasiswa (aktif → cuti → aktif
 * kembali → lulus, dst.) — sumber timeline "Riwayat Akademik". Diisi
 * otomatis oleh StudentObserver setiap kali students.status berubah,
 * dari jalur manapun (pengajuan yang disetujui, Bagian Akademik, seeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_status_histories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->date('effective_date');
            $table->foreignUlid('academic_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->foreignUlid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['student_id', 'effective_date']);
            $table->index(['university_id', 'to_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_status_histories');
    }
};
