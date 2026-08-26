<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            // Opsional — menandai soal ini relevan untuk mata kuliah tertentu
            // agar mudah dicari/difilter lintas semester, tanpa mengikat bank
            // soal ke satu ujian/kelas tertentu (lihat catatan desain di
            // docs/EXAM_RANDOMIZATION.md §11).
            $table->foreignUlid('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->text('question_text');
            $table->decimal('points', 5, 2)->default(1);
            $table->timestampsTz();

            $table->index(['university_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_items');
    }
};
