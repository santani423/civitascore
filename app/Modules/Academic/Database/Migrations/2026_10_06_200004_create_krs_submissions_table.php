<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Kartu" KRS satu mahasiswa untuk satu semester — status pengajuan
 * (draft → submitted → approved/rejected) beserta keputusan dosen wali.
 * Baris mata kuliahnya tetap di krs_items (status draft/pending/enrolled),
 * tabel ini hanya menyimpan status & jejak persetujuan per semester.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('krs_submissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('academic_term_id')->constrained('academic_terms')->cascadeOnDelete();
            $table->string('status', 20);
            $table->unsignedSmallInteger('total_credits')->default(0);
            $table->unsignedSmallInteger('max_credits')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestampsTz();

            $table->unique(['student_id', 'academic_term_id']);
            $table->index(['university_id', 'academic_term_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('krs_submissions');
    }
};
