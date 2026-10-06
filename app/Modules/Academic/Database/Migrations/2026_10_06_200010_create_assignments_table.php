<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tugas kelas (RANCANGAN-APLIKASI.md §4.12): batas waktu, aturan
 * keterlambatan & pengumpulan ulang, format & ukuran maksimal berkas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUlid('attachment_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->timestampTz('due_at');
            $table->boolean('allow_late_submission')->default(false);
            $table->boolean('allow_resubmission')->default(true);
            $table->unsignedSmallInteger('max_file_size_mb')->default(10);
            // Daftar ekstensi berkas yang diterima, mis. ["pdf","docx"].
            $table->json('allowed_extensions')->nullable();
            $table->decimal('max_score', 5, 2)->default(100);
            $table->boolean('is_published')->default(true);
            $table->timestampTz('published_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['class_section_id', 'due_at']);
            $table->index(['university_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
