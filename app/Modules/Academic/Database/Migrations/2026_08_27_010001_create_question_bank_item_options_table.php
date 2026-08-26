<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank_item_options', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('question_bank_item_id')->constrained('question_bank_items')->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('order_index')->default(0);
            $table->timestampsTz();

            $table->index(['university_id', 'question_bank_item_id'], 'qbi_options_university_id_item_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_item_options');
    }
};
