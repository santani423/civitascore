<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            // Bobot ujian ini terhadap komponen penilaian mata kuliah (mis.
            // UTS = 30%) — nullable berarti ujian ini tidak dipakai dalam
            // perhitungan nilai berbobot (spec §9). Bukan tabel komponen
            // penilaian baru — lingkupnya sengaja dibatasi ke bobot per
            // ujian, bukan sistem penilaian mata kuliah yang terpisah.
            $table->decimal('weight_percentage', 5, 2)->nullable()->after('max_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn('weight_percentage');
        });
    }
};
