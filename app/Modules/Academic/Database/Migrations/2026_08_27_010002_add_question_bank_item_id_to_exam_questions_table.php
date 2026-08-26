<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table): void {
            // Provenance — soal ini diterapkan dari bank soal mana (kalau
            // ada). Ini SNAPSHOT, bukan referensi hidup: mengubah bank soal
            // tidak mengubah soal yang sudah diterapkan ke ujian manapun.
            // Dipakai untuk mencegah penerapan ganda item bank yang sama ke
            // ujian yang sama (lihat QuestionBankService::applyToExam()).
            $table->foreignUlid('question_bank_item_id')->nullable()->after('exam_id')
                ->constrained('question_bank_items')->nullOnDelete();

            $table->index(['exam_id', 'question_bank_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table): void {
            $table->dropForeign(['question_bank_item_id']);
            $table->dropColumn('question_bank_item_id');
        });
    }
};
