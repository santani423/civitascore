<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mata kuliah prasyarat (RANCANGAN-APLIKASI.md §4.10 "Validasi mata
 * kuliah prasyarat"). `min_letter_grade` null = batas lulus default
 * (lihat KrsPlanService::PASSING_GRADE).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_prerequisites', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUlid('prerequisite_course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('min_letter_grade', 5)->nullable();
            $table->timestampsTz();

            $table->unique(['course_id', 'prerequisite_course_id']);
            $table->index(['university_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_prerequisites');
    }
};
