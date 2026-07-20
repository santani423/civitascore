<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Library\Enums\BookLoanStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_loans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('book_id')->constrained('books')->cascadeOnDelete();
            // students belongs to the Academic module — cross-module FK by
            // table name string is normal in this codebase (see KrsItem's
            // FK to class_sections/academic_terms for precedent).
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('borrowed_at');
            $table->date('due_at');
            $table->date('returned_at')->nullable();
            $table->enum('status', array_column(BookLoanStatus::cases(), 'value'));
            $table->decimal('fine_amount', 10, 2)->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'status']);
            $table->index(['university_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_loans');
    }
};
