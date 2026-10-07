<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengumpulan tugas satu mahasiswa — diikat ke KrsItem (bukan langsung ke
 * students) supaya kepemilikan & hak ikut kelas tervalidasi dari KRS yang
 * sama dengan ujian/absensi/nilai. Satu baris per (tugas, KRS): pengumpulan
 * ulang mengganti berkas, `submission_count` mencatat berapa kali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignUlid('krs_item_id')->constrained('krs_items')->cascadeOnDelete();
            $table->foreignUlid('file_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestampTz('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->unsignedSmallInteger('submission_count')->default(1);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestampTz('graded_at')->nullable();
            $table->foreignUlid('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['assignment_id', 'krs_item_id']);
            $table->index(['university_id', 'krs_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
