<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('study_program_id')->constrained('study_programs')->cascadeOnDelete();
            $table->foreignUlid('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedTinyInteger('credits');
            $table->unsignedTinyInteger('semester_level');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'study_program_id']);
            $table->index(['university_id', 'curriculum_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
