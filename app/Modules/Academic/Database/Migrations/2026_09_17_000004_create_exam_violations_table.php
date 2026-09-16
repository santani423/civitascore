<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Enums\ExamViolationType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_violations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->enum('violation_type', array_column(ExamViolationType::cases(), 'value'));
            $table->unsignedInteger('sequence_number');
            $table->decimal('penalty_points', 5, 2)->default(1);
            $table->timestampTz('occurred_at');
            // Metadata seperlunya saja (mis. { "path": "/portal/ujian/123" })
            // — tidak pernah menyimpan sesuatu yang sensitif (spec §4).
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->unique(['exam_attempt_id', 'sequence_number']);
            $table->index(['university_id', 'exam_attempt_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_violations');
    }
};
