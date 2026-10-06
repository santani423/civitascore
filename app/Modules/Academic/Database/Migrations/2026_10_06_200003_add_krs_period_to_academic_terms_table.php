<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Periode pengisian KRS per semester (RANCANGAN-APLIKASI.md §4.10
 * "Pengaturan periode KRS"). Keduanya null = periode belum diatur, KRS
 * mandiri mahasiswa dianggap tertutup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_terms', function (Blueprint $table): void {
            $table->date('krs_start_date')->nullable()->after('end_date');
            $table->date('krs_end_date')->nullable()->after('krs_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('academic_terms', function (Blueprint $table): void {
            $table->dropColumn(['krs_start_date', 'krs_end_date']);
        });
    }
};
