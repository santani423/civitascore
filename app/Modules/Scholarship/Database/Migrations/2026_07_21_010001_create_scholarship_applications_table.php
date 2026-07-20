<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Scholarship\Enums\ScholarshipApplicationStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_applications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('scholarship_id')->constrained('scholarships')->cascadeOnDelete();
            // scholarship_applications belongs to the Scholarship module, but
            // students are owned by the Academic module — cross-module FK by
            // table name string, same pattern as Finance's invoices -> students.
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', array_column(ScholarshipApplicationStatus::cases(), 'value'));
            $table->timestampTz('submitted_at');
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['scholarship_id', 'student_id']);
            $table->index(['university_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_applications');
    }
};
