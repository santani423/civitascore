<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            // `score` sudah ada dan tetap berarti "final score" (dipakai apa
            // adanya oleh semua consumer lama — StudentExamResource, PDF,
            // frontend) supaya tidak ada perubahan yang breaking. Kolom baru
            // di sini hanya melengkapi rincian di baliknya, lihat
            // ExamService::finalizeAttempt().
            $table->decimal('raw_score', 5, 2)->nullable()->after('score');
            $table->decimal('penalty_score', 5, 2)->default(0)->after('raw_score');
            $table->string('grade', 5)->nullable()->after('penalty_score');
            $table->decimal('weighted_score', 5, 2)->nullable()->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropColumn(['raw_score', 'penalty_score', 'grade', 'weighted_score']);
        });
    }
};
