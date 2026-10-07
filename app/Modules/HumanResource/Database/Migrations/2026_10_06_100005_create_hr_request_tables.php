<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HumanResource\Enums\HrRequestStatus;
use Modules\HumanResource\Enums\HrRequestType;
use Modules\HumanResource\Enums\LeaveStatus;
use Modules\HumanResource\Enums\LeaveType;

/**
 * Pengajuan SDM. `hr_requests` adalah satu pintu semua jenis pengajuan
 * (cuti, mutasi, perubahan data, kenaikan jabatan, dokumen) dan menjadi
 * `requestable` di Modul ApprovalWorkflow yang sudah ada — mesin
 * persetujuan tidak dibangun ulang. Kolom approved_by/rejected_by/
 * approval_note adalah ringkasan keputusan akhir untuk tampilan & laporan;
 * jejak lengkap per langkah tetap di approval_actions/approval_histories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('leave_type', array_column(LeaveType::cases(), 'value'));
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');
            $table->text('reason');
            $table->foreignUlid('attachment_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->enum('status', array_column(LeaveStatus::cases(), 'value'))->default(LeaveStatus::Draft->value);
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->foreignUlid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('rejected_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'status']);
            $table->index(['employee_id', 'start_date']);
        });

        Schema::create('hr_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', array_column(HrRequestType::cases(), 'value'));
            $table->string('title');
            $table->text('description')->nullable();
            // Isi formulir pengajuan (mis. field yang diminta diubah pada
            // pengajuan perubahan data) — snapshot permintaan, bukan data
            // relasional; perubahan sebenarnya baru ditulis ke tabel
            // masing-masing setelah disetujui.
            $table->json('payload')->nullable();
            $table->foreignUlid('leave_request_id')->nullable()->unique()->constrained('leave_requests')->nullOnDelete();
            $table->foreignUlid('attachment_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->enum('status', array_column(HrRequestStatus::cases(), 'value'))->default(HrRequestStatus::Pending->value);
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('approval_request_id')->nullable()->constrained('approval_requests')->nullOnDelete();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->foreignUlid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('rejected_at')->nullable();
            $table->text('approval_note')->nullable();
            // Untuk mutasi/kenaikan jabatan/dokumen: persetujuan baru berarti
            // "boleh diproses" — processed_at menandai SDM sudah benar-benar
            // menindaklanjuti (membuat SK/mutasi), dasar alert dashboard.
            $table->timestampTz('processed_at')->nullable();
            $table->foreignUlid('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'status', 'type']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_requests');
        Schema::dropIfExists('leave_requests');
    }
};
