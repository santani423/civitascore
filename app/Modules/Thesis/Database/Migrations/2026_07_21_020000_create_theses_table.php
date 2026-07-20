<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Thesis\Enums\ThesisStatus;
use Modules\Thesis\Enums\ThesisType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('supervisor_lecturer_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->string('title');
            $table->enum('thesis_type', array_column(ThesisType::cases(), 'value'));
            $table->enum('status', array_column(ThesisStatus::cases(), 'value'));
            $table->timestampTz('submitted_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theses');
    }
};
