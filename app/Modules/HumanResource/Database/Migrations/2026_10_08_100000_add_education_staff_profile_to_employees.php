<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Profil khusus tenaga kependidikan (RANCANGAN-AKUN-SDM §5.4) di atas
 * master `employees`: penugasan laboratorium/fasilitas dan ringkasan
 * kompetensi. Jabatan fungsional tendik memakai `position_id` (master
 * positions bertipe fungsional) yang sudah ada, tidak diduplikasi.
 *
 * Kategori tendik diperluas dengan Keuangan, Keamanan, dan Pengemudi.
 */
return new class extends Migration
{
    private const CATEGORIES = ['administration', 'laboratory', 'technician', 'librarian', 'archivist', 'it', 'finance', 'security', 'driver', 'other'];

    private const LEGACY_CATEGORIES = ['administration', 'laboratory', 'technician', 'librarian', 'archivist', 'it', 'other'];

    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->enum('staff_category', self::CATEGORIES)->nullable()->change();
            $table->string('assigned_facility')->nullable()->after('staff_category');
            $table->text('competency_summary')->nullable()->after('assigned_facility');

            $table->index(['university_id', 'staff_category']);
        });
    }

    public function down(): void
    {
        DB::table('employees')
            ->whereNotIn('staff_category', self::LEGACY_CATEGORIES)
            ->update(['staff_category' => 'other']);

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropIndex(['university_id', 'staff_category']);
            $table->dropColumn(['assigned_facility', 'competency_summary']);
            $table->enum('staff_category', self::LEGACY_CATEGORIES)->nullable()->change();
        });
    }
};
