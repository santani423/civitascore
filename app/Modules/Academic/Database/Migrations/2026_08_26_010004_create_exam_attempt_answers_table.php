<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_answers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignUlid('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->foreignUlid('exam_question_option_id')->nullable()->constrained('exam_question_options')->nullOnDelete();
            $table->timestampTz('answered_at');
            $table->timestampsTz();

            $table->unique(['exam_attempt_id', 'exam_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_answers');
    }
};
