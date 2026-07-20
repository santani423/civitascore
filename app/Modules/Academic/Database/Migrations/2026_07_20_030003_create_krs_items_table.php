<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Enums\KrsItemStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('krs_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            // Denormalized from class_sections.academic_term_id so KRS can
            // be filtered by period directly, without joining two
            // TenantScoped tables (see plan notes on the ambiguous-column
            // issue with join() across TenantScoped models).
            $table->foreignUlid('academic_term_id')->constrained('academic_terms')->cascadeOnDelete();
            $table->enum('status', array_column(KrsItemStatus::cases(), 'value'));
            $table->timestampsTz();

            $table->unique(['student_id', 'class_section_id']);
            $table->index(['university_id', 'academic_term_id']);
            $table->index(['university_id', 'student_id']);
            $table->index(['university_id', 'class_section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('krs_items');
    }
};
