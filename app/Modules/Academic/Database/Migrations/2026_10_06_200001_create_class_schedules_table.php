<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal pertemuan mingguan sebuah kelas (hari, jam, ruangan). Tabel
 * terpisah dari class_sections karena satu kelas bisa bertemu lebih dari
 * sekali seminggu (mis. teori + praktikum). Dipakai deteksi bentrok KRS,
 * jadwal kuliah, dan kalender mahasiswa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            // ISO-8601: 1 = Senin ... 7 = Minggu.
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 100)->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'class_section_id']);
            $table->index(['university_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
