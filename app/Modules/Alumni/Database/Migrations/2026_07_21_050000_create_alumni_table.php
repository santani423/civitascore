<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Alumni\Enums\AlumniEmploymentStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->integer('graduation_year');
            $table->enum('employment_status', array_column(AlumniEmploymentStatus::cases(), 'value'));
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->integer('waiting_period_months')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestampsTz();

            $table->unique('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
