<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penargetan tambahan di atas target_scope yang sudah ada
 * (universitas/fakultas/program studi): sasaran peran (semua/mahasiswa/
 * dosen/pegawai), angkatan, dan semester — dipakai feed pengumuman portal
 * mahasiswa. Semua kolom opsional, jadi pengumuman lama tetap berlaku
 * untuk semua orang seperti sebelumnya. Lampiran memakai FileManagement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('audience', 20)->default('all')->after('target_id');
            $table->unsignedSmallInteger('target_admission_year')->nullable()->after('audience');
            $table->unsignedTinyInteger('target_semester')->nullable()->after('target_admission_year');
            $table->foreignUlid('attachment_file_id')->nullable()->after('body')->constrained('file_uploads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('attachment_file_id');
            $table->dropColumn(['audience', 'target_admission_year', 'target_semester']);
        });
    }
};
