<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Atasan langsung (employees.supervisor_employee_id) & kepala unit
 *    (work_units.head_employee_id) — dasar approver `direct_supervisor` /
 *    `unit_head` di ApprovalWorkflow (HrApproverResolver).
 * 2. Identitas pegawai yang belum ada: gelar, agama, status perkawinan,
 *    kontak darurat, NPWP & rekening. NPWP/rekening disimpan terenkripsi
 *    (cast `encrypted` di model) — karena itu kolom text, bukan string.
 * 3. Status `returned` (dikembalikan ke pemohon) pada pengajuan SDM & cuti:
 *    kolom enum diganti string, nilai sah dijaga cast enum (preseden
 *    widen_status_on_krs_items_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->foreignUlid('supervisor_employee_id')->nullable()->after('rank_id')->constrained('employees')->nullOnDelete();
            $table->string('front_title', 50)->nullable()->after('name');
            $table->string('back_title', 100)->nullable()->after('front_title');
            $table->string('religion', 30)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->text('npwp')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->text('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
        });

        Schema::table('work_units', function (Blueprint $table): void {
            $table->foreignUlid('head_employee_id')->nullable()->after('study_program_id')->constrained('employees')->nullOnDelete();
        });

        Schema::table('hr_requests', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->change();
            $table->timestampTz('returned_at')->nullable()->after('rejected_at');
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->string('status', 20)->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('hr_requests', function (Blueprint $table): void {
            $table->dropColumn('returned_at');
        });

        Schema::table('work_units', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('head_employee_id');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('supervisor_employee_id');
            $table->dropColumn([
                'front_title', 'back_title', 'religion', 'marital_status', 'emergency_contact_name',
                'emergency_contact_phone', 'npwp', 'bank_name', 'bank_account_number', 'bank_account_name',
            ]);
        });
    }
};
