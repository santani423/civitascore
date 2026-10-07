<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KRS mandiri menambah status draft (rencana mahasiswa) dan pending
 * (menunggu persetujuan dosen wali) di samping enrolled/dropped. Kolom
 * enum diganti string — nilai sah tetap dijaga cast KrsItemStatus — supaya
 * penambahan status berikutnya tidak perlu ALTER enum lagi.
 *
 * `krs_submission_id` menautkan baris yang diisi lewat KRS mandiri ke
 * pengajuannya; baris yang didaftarkan langsung oleh Bagian Akademik
 * (KrsItemController) tetap null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('krs_items', function (Blueprint $table): void {
            $table->string('status', 20)->change();
            $table->foreignUlid('krs_submission_id')->nullable()->after('academic_term_id')->constrained('krs_submissions')->nullOnDelete();
            $table->index(['student_id', 'academic_term_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('krs_items', function (Blueprint $table): void {
            $table->dropIndex(['student_id', 'academic_term_id', 'status']);
            $table->dropConstrainedForeignId('krs_submission_id');
            $table->enum('status', ['enrolled', 'dropped'])->change();
        });
    }
};
