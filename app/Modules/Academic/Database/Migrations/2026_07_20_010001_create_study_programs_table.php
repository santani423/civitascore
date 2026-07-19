<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_programs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('faculty_id')->constrained('faculties')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('degree_level');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'faculty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_programs');
    }
};
