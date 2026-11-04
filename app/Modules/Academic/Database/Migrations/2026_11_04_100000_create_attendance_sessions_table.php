<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->unsignedTinyInteger('meeting_number');
            $table->date('meeting_date');
            $table->string('secret', 64);
            $table->foreignUlid('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('expires_at');
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();

            $table->index(['class_section_id', 'meeting_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
