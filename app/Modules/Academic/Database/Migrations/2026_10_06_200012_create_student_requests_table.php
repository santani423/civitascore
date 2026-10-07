<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan akademik mahasiswa (cuti, aktif kembali, perubahan data,
 * surat, lainnya). Keputusannya TIDAK disimpan di sistem persetujuan
 * kedua: setiap pengajuan yang dikirim membuat ApprovalRequest di Modul
 * ApprovalWorkflow (pola yang sama dengan hr_requests), lalu keputusan
 * akhirnya disalin balik ke baris ini oleh SyncStudentRequestDecision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('status', 20);
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->foreignUlid('attachment_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->foreignUlid('academic_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->foreignUlid('approval_request_id')->nullable()->constrained('approval_requests')->nullOnDelete();
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['university_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_requests');
    }
};
