<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modul KEU memperluas `invoices` & `payments` yang sudah ada (rancangan
 * KEU §9.2, KD-KEU-01/03) — arti kolom lama dipertahankan sehingga posting
 * PMB (AdmissionPostingService), dashboard, dan laporan lama tetap benar:
 *
 * - `invoices.amount`      = neto kewajiban saat ini (bruto − potongan ± penyesuaian)
 * - `invoices.paid_amount` = Σ alokasi `payments` berstatus posted
 * - `payments`             = alokasi dana sukses ke satu tagihan/termin
 *
 * `status` enum → string(20) supaya status baru (draft/cancelled/waived)
 * tidak memerlukan ALTER enum lagi (pola krs_items). Semua kolom baru
 * nullable/berdefault — data lama tetap valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('status', 20)->default('unpaid')->change();
            $table->string('invoice_number', 40)->nullable()->after('id');
            $table->foreignUlid('academic_term_id')->nullable()->after('student_id')->constrained('academic_terms')->nullOnDelete();
            $table->string('bill_group', 30)->default('registration')->after('period');
            $table->ulid('parent_invoice_id')->nullable()->after('bill_group');
            $table->decimal('gross_amount', 14, 2)->default(0)->after('parent_invoice_id');
            $table->decimal('discount_amount', 14, 2)->default(0)->after('gross_amount');
            $table->decimal('adjustment_amount', 14, 2)->default(0)->after('discount_amount');
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('settled_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->date('overdue_since')->nullable();
            $table->json('rate_snapshot')->nullable();
            $table->ulid('billing_run_id')->nullable();
            $table->boolean('needs_resolution')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['university_id', 'invoice_number']);
            $table->index(['university_id', 'academic_term_id', 'bill_group']);
            $table->index(['university_id', 'status', 'due_date']);
            $table->index(['parent_invoice_id']);
            $table->index(['billing_run_id']);
        });

        // Data lama: bruto = neto (belum ada rincian potongan), tanggal terbit = dibuat,
        // tagihan hasil posting PMB dikelompokkan sebagai `admission`.
        DB::table('invoices')->update(['gross_amount' => DB::raw('amount'), 'issued_at' => DB::raw('created_at')]);
        DB::table('invoices')->where('source_type', 'like', 'pmb_invoice%')->update(['bill_group' => 'admission']);

        Schema::table('payments', function (Blueprint $table): void {
            $table->ulid('payment_transaction_id')->nullable()->after('invoice_id');
            $table->ulid('invoice_installment_id')->nullable()->after('payment_transaction_id');
            $table->string('allocation_type', 20)->default('payment')->after('amount');
            $table->string('status', 20)->default('posted')->after('allocation_type');
            $table->timestampTz('effective_at')->nullable();
            $table->timestampTz('reversed_at')->nullable();
            $table->foreignUlid('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reversal_reason', 255)->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['payment_transaction_id']);
            $table->index(['invoice_installment_id']);
            $table->index(['university_id', 'status', 'paid_at']);
        });
    }

    public function down(): void
    {
        if (DB::table('invoices')->whereIn('status', ['draft', 'cancelled', 'waived'])->exists()) {
            throw new RuntimeException('invoices berisi status KEU baru; rollback dibatalkan.');
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['payment_transaction_id']);
            $table->dropIndex(['invoice_installment_id']);
            $table->dropIndex(['university_id', 'status', 'paid_at']);
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn(['payment_transaction_id', 'invoice_installment_id', 'allocation_type', 'status', 'effective_at', 'reversed_at', 'reversal_reason']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['university_id', 'invoice_number']);
            $table->dropIndex(['university_id', 'academic_term_id', 'bill_group']);
            $table->dropIndex(['university_id', 'status', 'due_date']);
            $table->dropIndex(['parent_invoice_id']);
            $table->dropIndex(['billing_run_id']);
            $table->dropConstrainedForeignId('academic_term_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'invoice_number', 'bill_group', 'parent_invoice_id', 'gross_amount', 'discount_amount', 'adjustment_amount',
                'issued_at', 'settled_at', 'cancelled_at', 'cancel_reason', 'is_overdue', 'overdue_since', 'rate_snapshot',
                'billing_run_id', 'needs_resolution', 'notes', 'lock_version',
            ]);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->change();
        });
    }
};
