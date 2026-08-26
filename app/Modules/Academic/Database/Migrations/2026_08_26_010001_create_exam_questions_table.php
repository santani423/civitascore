<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->text('question_text');
            $table->decimal('points', 5, 2)->default(1);
            $table->unsignedInteger('order_index')->default(0);
            // Drives "manual" question_selection_mode — the lecturer flags
            // which pool questions make up the fixed set every participant
            // receives (spec §4 Mode C). Ignored for all/random modes.
            $table->boolean('is_selected')->default(true);
            $table->timestampsTz();

            $table->index(['university_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
