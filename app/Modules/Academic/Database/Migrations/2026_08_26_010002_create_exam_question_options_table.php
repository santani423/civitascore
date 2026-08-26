<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_question_options', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('order_index')->default(0);
            $table->timestampsTz();

            $table->index(['university_id', 'exam_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_question_options');
    }
};
