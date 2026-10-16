<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pencekalan (docs/academic-learning/01-siakad.md A.8) — tahap pertama:
 * cekal milik SIAKAD yang dapat diterbitkan sumber mana pun (saat ini
 * sumber `finance` dari Modul KEU), dispensasi per layanan, dan riwayat
 * append-only. Tabel `academic_block_rules` generik belum dibuat; aturan
 * keuangan disimpan Modul KEU (`finance_clearance_rules`).
 *
 * `active_key` unik menjaga satu cekal aktif per (mahasiswa, sumber, aturan)
 * — dikosongkan saat cekal dicabut sehingga cekal baru dapat terbit lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_blocks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('academic_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->string('block_type', 30);
            $table->string('source', 30);
            $table->string('source_rule_code', 40)->nullable();
            $table->json('restricted_services');
            $table->text('reason');
            $table->json('details')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('active_key', 160)->nullable()->unique();
            $table->timestampTz('effective_from');
            $table->foreignUlid('imposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoke_reason', 255)->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'status']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('academic_block_exemptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('academic_block_id')->constrained('academic_blocks')->cascadeOnDelete();
            $table->string('service', 30);
            $table->timestampTz('valid_from');
            $table->timestampTz('valid_until');
            $table->text('reason');
            $table->decimal('promise_amount', 14, 2)->nullable();
            $table->date('promise_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampsTz();
            $table->index(['academic_block_id', 'service', 'status']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('academic_block_histories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('academic_block_id')->constrained('academic_blocks')->cascadeOnDelete();
            $table->string('action', 30);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestampsTz();
            $table->index(['academic_block_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_block_histories');
        Schema::dropIfExists('academic_block_exemptions');
        Schema::dropIfExists('academic_blocks');
    }
};
