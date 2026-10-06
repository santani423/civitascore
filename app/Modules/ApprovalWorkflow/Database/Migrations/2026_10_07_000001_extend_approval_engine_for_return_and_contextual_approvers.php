<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perluasan mesin persetujuan untuk kebutuhan SDM:
 *  - pengajuan dapat "dikembalikan ke pemohon" untuk direvisi lalu
 *    diajukan ulang (status `returned`, aksi `return`, event
 *    `returned_to_requester`/`resubmitted`),
 *  - approver kontekstual: pemegang jabatan (`position`), atasan langsung
 *    (`direct_supervisor`), dan kepala unit (`unit_head`) — diresolusi lewat
 *    kontrak ContextualApproverResolver yang diisi modul SDM.
 *
 * Kolom enum diganti string — nilai sah tetap dijaga cast enum masing-
 * masing — mengikuti preseden widen_status_on_krs_items_table, supaya
 * penambahan status berikutnya tidak perlu ALTER enum lagi.
 *
 * approver_position_id sengaja tanpa FK: tabel `positions` milik modul SDM,
 * mesin persetujuan generik tidak boleh bergantung padanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_workflow_steps', function (Blueprint $table): void {
            $table->string('approver_type', 30)->change();
            $table->ulid('approver_position_id')->nullable()->after('approver_user_id')->index();
        });

        Schema::table('approval_requests', function (Blueprint $table): void {
            $table->string('status', 20)->change();
            $table->unsignedSmallInteger('resubmission_count')->default(0)->after('status');
            $table->timestampTz('returned_at')->nullable()->after('submitted_at');
        });

        Schema::table('approval_request_steps', function (Blueprint $table): void {
            $table->string('status', 20)->change();
        });

        Schema::table('approval_actions', function (Blueprint $table): void {
            $table->string('action', 20)->change();
        });

        Schema::table('approval_histories', function (Blueprint $table): void {
            $table->string('event', 40)->change();
        });
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table): void {
            $table->dropColumn(['resubmission_count', 'returned_at']);
        });

        Schema::table('approval_workflow_steps', function (Blueprint $table): void {
            $table->dropIndex(['approver_position_id']);
            $table->dropColumn('approver_position_id');
        });
    }
};
