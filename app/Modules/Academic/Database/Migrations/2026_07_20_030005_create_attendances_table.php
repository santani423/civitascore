<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Enums\AttendanceStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('krs_item_id')->constrained('krs_items')->cascadeOnDelete();
            $table->unsignedTinyInteger('meeting_number');
            $table->date('meeting_date');
            $table->enum('status', array_column(AttendanceStatus::cases(), 'value'));
            $table->string('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['krs_item_id', 'meeting_number']);
            $table->index(['university_id', 'meeting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
