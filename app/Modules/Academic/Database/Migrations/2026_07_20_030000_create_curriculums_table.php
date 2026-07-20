<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculums', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('study_program_id')->constrained('study_programs')->cascadeOnDelete();
            $table->string('name');
            $table->string('academic_year');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['university_id', 'study_program_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculums');
    }
};
