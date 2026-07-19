<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Enums\InvoiceStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('period');
            $table->decimal('amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->enum('status', array_column(InvoiceStatus::cases(), 'value'));
            $table->date('due_date');
            $table->timestampsTz();

            $table->index(['university_id', 'status']);
            $table->index(['university_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
