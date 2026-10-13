<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PMB — tagihan & pembayaran (docs/pmb/10 §4.5; KD-03: tagihan PMB terpisah
 * dari `invoices` Finance, diposting saat konversi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pmb_invoices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained('pmb_applications')->restrictOnDelete();
            $table->string('type', 30);
            $table->string('invoice_number', 40);
            $table->char('currency', 3)->default('IDR');
            $table->decimal('amount_gross', 14, 2);
            $table->decimal('amount_discount', 14, 2)->default(0);
            $table->decimal('amount_net', 14, 2);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('minimum_for_enrollment', 14, 2)->nullable();
            $table->string('status', 30)->default('unpaid');
            $table->timestampTz('due_at');
            $table->timestampTz('paid_at')->nullable();
            $table->boolean('needs_resolution')->default(false);
            $table->string('resolution_note', 255)->nullable();
            // Tanpa FK keras ke `invoices`: modul Finance tidak bergantung pada PMB.
            $table->ulid('finance_invoice_id')->nullable()->index();
            $table->string('active_key', 80)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestampsTz();
            $table->unique(['university_id', 'invoice_number']);
            $table->unique('active_key');
            $table->index(['university_id', 'type', 'status']);
            $table->index('due_at');
        });

        Schema::create('pmb_vouchers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('applies_to', 30);
            $table->string('discount_type', 10);
            $table->decimal('discount_value', 14, 2);
            $table->decimal('max_discount', 14, 2)->nullable();
            $table->json('scope')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestampTz('valid_from');
            $table->timestampTz('valid_until');
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('pmb_invoice_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('invoice_id')->constrained('pmb_invoices')->cascadeOnDelete();
            $table->string('item_type', 20);
            $table->string('source_type', 30);
            $table->ulid('source_id')->nullable();
            $table->string('code', 30);
            $table->string('description', 255);
            $table->decimal('amount', 14, 2);
            $table->unsignedTinyInteger('installment_seq')->nullable();
            $table->timestampTz('due_at')->nullable();
            $table->timestampsTz();
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('pmb_payment_transactions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('invoice_id')->constrained('pmb_invoices')->restrictOnDelete();
            $table->string('channel', 30);
            $table->string('provider', 30);
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('IDR');
            $table->string('status', 20)->default('created');
            $table->string('idempotency_key', 100);
            $table->string('provider_reference', 100)->nullable();
            $table->string('va_number', 40)->nullable();
            $table->text('qr_string')->nullable();
            $table->string('redirect_url', 500)->nullable();
            $table->json('instructions')->nullable();
            $table->unsignedSmallInteger('unique_code')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->string('provider_status_raw', 50)->nullable();
            $table->string('receipt_number', 40)->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('failure_reason', 255)->nullable();
            $table->unsignedSmallInteger('recheck_attempts')->default(0);
            $table->timestampTz('next_recheck_at')->nullable();
            $table->timestampsTz();
            $table->unique(['invoice_id', 'idempotency_key']);
            $table->unique(['provider', 'provider_reference']);
            $table->index(['university_id', 'status', 'created_at']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('pmb_payment_callbacks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // Nullable: tenant baru diketahui setelah transaksi ditemukan (10 §4.5).
            $table->foreignUlid('university_id')->nullable()->constrained('universities')->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('provider_event_id', 150);
            $table->foreignUlid('transaction_id')->nullable()->constrained('pmb_payment_transactions')->nullOnDelete();
            $table->boolean('signature_valid');
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->timestampTz('received_at');
            $table->timestampTz('processed_at')->nullable();
            $table->string('processing_result', 30)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('next_attempt_at')->nullable();
            $table->unique(['provider', 'provider_event_id']);
            $table->index('transaction_id');
        });

        Schema::create('pmb_manual_payment_proofs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('transaction_id')->unique()->constrained('pmb_payment_transactions')->cascadeOnDelete();
            $table->foreignUlid('file_upload_id')->constrained('file_uploads')->restrictOnDelete();
            $table->string('sender_bank', 60);
            $table->string('sender_name', 150);
            $table->date('transfer_date');
            $table->decimal('transferred_amount', 14, 2);
            $table->string('status', 20)->default('submitted');
            $table->string('reason_code', 40)->nullable();
            $table->text('note')->nullable();
            $table->boolean('duplicate_warning')->default(false);
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'status']);
        });

        Schema::create('pmb_reconciliation_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('provider', 30);
            $table->date('statement_date');
            $table->foreignUlid('source_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('matched_items')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['university_id', 'provider', 'statement_date']);
        });

        Schema::create('pmb_reconciliation_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('batch_id')->constrained('pmb_reconciliation_batches')->cascadeOnDelete();
            $table->foreignUlid('transaction_id')->nullable()->constrained('pmb_payment_transactions')->nullOnDelete();
            $table->string('external_reference', 100)->nullable();
            $table->decimal('external_amount', 14, 2)->nullable();
            $table->date('external_date')->nullable();
            $table->string('external_description', 255)->nullable();
            $table->string('result', 30);
            $table->string('resolution', 30)->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignUlid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
            $table->index(['batch_id', 'result']);
        });

        Schema::create('pmb_refunds', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('transaction_id')->constrained('pmb_payment_transactions')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('reason_code', 40);
            $table->text('reason_note');
            $table->text('beneficiary_bank');
            $table->text('beneficiary_account');
            $table->text('beneficiary_name');
            $table->string('status', 20)->default('draft');
            $table->ulid('approval_request_id')->nullable()->index();
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->foreignUlid('proof_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('batch_reference', 40)->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach ([
            'pmb_refunds', 'pmb_reconciliation_items', 'pmb_reconciliation_batches', 'pmb_manual_payment_proofs',
            'pmb_payment_callbacks', 'pmb_payment_transactions', 'pmb_invoice_items', 'pmb_vouchers', 'pmb_invoices',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
