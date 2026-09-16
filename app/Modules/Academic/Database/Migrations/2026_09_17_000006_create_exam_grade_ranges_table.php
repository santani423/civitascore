<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_grade_ranges', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('exam_id')->constrained('exams')->cascadeOnDelete();
            // Nilai huruf pakai enum LetterGrade yang sudah ada (bukan set
            // baru) supaya konsisten dengan modul nilai KRS/IP (spec §8).
            $table->string('grade', 5);
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->timestampsTz();

            $table->unique(['exam_id', 'grade']);
            $table->index(['university_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_grade_ranges');
    }
};
