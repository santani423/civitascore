<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Enums\ExamAttemptStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignUlid('krs_item_id')->constrained('krs_items')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->enum('status', array_column(ExamAttemptStatus::cases(), 'value'))
                ->default(ExamAttemptStatus::InProgress->value);
            // Snapshot of the assignment resolved once at attempt start —
            // the source of truth for stability across refreshes (spec §14).
            // question_order: ordered array of exam_question_id.
            // option_order: { exam_question_id: [exam_question_option_id, ...] }.
            $table->json('question_order');
            $table->json('option_order');
            $table->timestampTz('started_at');
            $table->timestampTz('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestampsTz();

            $table->unique(['exam_id', 'krs_item_id', 'attempt_number']);
            $table->index(['university_id', 'krs_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
