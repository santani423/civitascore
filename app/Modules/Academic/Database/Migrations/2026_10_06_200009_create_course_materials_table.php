<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materi perkuliahan per kelas, dikelompokkan per pertemuan
 * (RANCANGAN-APLIKASI.md §4.11). Berkas memakai FileManagement
 * (file_uploads, fileable = CourseMaterial) — tidak ada penyimpanan
 * berkas kedua; link/video cukup URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_materials', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->unsignedTinyInteger('meeting_number')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 20);
            $table->string('url', 2048)->nullable();
            $table->foreignUlid('file_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->timestampTz('published_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['class_section_id', 'meeting_number']);
            $table->index(['university_id', 'class_section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
