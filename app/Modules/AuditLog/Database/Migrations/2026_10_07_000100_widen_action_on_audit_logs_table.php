<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log kini juga mencatat aksi non-CRUD (approve, verify, download,
 * lihat data sensitif, dst.). Kolom enum diganti string — nilai sah dijaga
 * cast AuditAction — supaya aksi berikutnya tidak perlu ALTER enum lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('action', 30)->change();
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan ke enum: baris dengan aksi baru akan gagal.
    }
};
