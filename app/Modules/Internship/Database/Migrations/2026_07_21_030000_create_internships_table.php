<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Internship\Enums\InternshipProgramType;
use Modules\Internship\Enums\InternshipStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('program_type', array_column(InternshipProgramType::cases(), 'value'));
            $table->string('institution_name');
            $table->string('position')->nullable();
            $table->foreignUlid('supervisor_lecturer_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', array_column(InternshipStatus::cases(), 'value'));
            $table->integer('sks_converted')->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'status']);
            $table->index(['university_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internships');
    }
};
