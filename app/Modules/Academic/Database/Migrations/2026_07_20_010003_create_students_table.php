<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Enums\StudentStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('study_program_id')->constrained('study_programs')->cascadeOnDelete();
            $table->string('nim');
            $table->string('name');
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('admission_year');
            $table->enum('status', array_column(StudentStatus::cases(), 'value'));
            $table->date('enrolled_at');
            $table->date('graduated_at')->nullable();
            $table->timestampsTz();

            $table->unique(['university_id', 'nim']);
            $table->index(['university_id', 'status']);
            $table->index(['university_id', 'study_program_id']);
            $table->index(['university_id', 'admission_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
