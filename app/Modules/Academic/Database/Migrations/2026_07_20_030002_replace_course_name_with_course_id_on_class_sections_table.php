<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * class_sections was built before the Course (mata kuliah) entity existed,
 * so it started with a free-text course_name. Now that Course is real,
 * replace it with a proper FK — avoids the same course name drifting
 * between class_sections and courses (see docs on this migration's PR).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sections', function (Blueprint $table): void {
            $table->dropColumn('course_name');
            $table->foreignUlid('course_id')->after('academic_term_id')->constrained('courses')->cascadeOnDelete();
            $table->index(['university_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::table('class_sections', function (Blueprint $table): void {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
            $table->string('course_name')->default('');
        });
    }
};
