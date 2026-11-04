<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Sumber baris presensi: `manual` (diisi dosen) atau `qr` (presensi mandiri mahasiswa). */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('method', 16)->default('manual')->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropColumn('method');
        });
    }
};
