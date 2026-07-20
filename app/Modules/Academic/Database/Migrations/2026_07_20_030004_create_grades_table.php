<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Enums\LetterGrade;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('krs_item_id')->constrained('krs_items')->cascadeOnDelete();
            $table->enum('letter_grade', array_column(LetterGrade::cases(), 'value'))->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampsTz();

            $table->unique('krs_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
