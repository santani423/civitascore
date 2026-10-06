<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agenda kalender akademik yang tidak bisa diturunkan dari data lain —
 * masa UTS/UAS, libur, wisuda, dsb. (RANCANGAN-APLIKASI.md §4.9 "Kalender
 * akademik terintegrasi"). Awal/akhir semester & periode KRS TIDAK
 * disimpan di sini; keduanya diturunkan langsung dari academic_terms.
 * `study_program_id` null = berlaku untuk seluruh universitas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_calendar_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('academic_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->foreignUlid('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 30);
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['university_id', 'start_date']);
            $table->index(['university_id', 'academic_term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_events');
    }
};
