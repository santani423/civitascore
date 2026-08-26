<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Enums\QuestionSelectionMode;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('questions_per_participant');
            $table->enum('question_selection_mode', array_column(QuestionSelectionMode::cases(), 'value'))
                ->default(QuestionSelectionMode::All->value);
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);
            $table->boolean('allow_back_navigation')->default(true);
            $table->boolean('show_result_after_submission')->default(true);
            $table->unsignedInteger('max_attempts')->default(1);
            $table->boolean('is_published')->default(false);
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'class_section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
