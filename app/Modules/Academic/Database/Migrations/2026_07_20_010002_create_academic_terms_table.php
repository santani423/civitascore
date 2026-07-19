<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Enums\AcademicSemester;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('academic_year');
            $table->enum('semester', array_column(AcademicSemester::cases(), 'value'));
            $table->boolean('is_current')->default(false);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestampsTz();

            $table->unique(['university_id', 'academic_year', 'semester']);
            $table->index(['university_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};
