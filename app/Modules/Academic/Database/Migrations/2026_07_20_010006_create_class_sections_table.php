<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sections', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('study_program_id')->constrained('study_programs')->cascadeOnDelete();
            $table->foreignUlid('academic_term_id')->constrained('academic_terms')->cascadeOnDelete();
            $table->string('course_name');
            $table->string('class_code');
            $table->unsignedInteger('capacity')->default(40);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['university_id', 'is_active', 'academic_term_id']);
            $table->index(['university_id', 'study_program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sections');
    }
};
