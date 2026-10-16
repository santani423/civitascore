<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel Modul KEU — Administrasi Keuangan Mahasiswa (rancangan
 * docs/RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md §9.3).
 * Seluruhnya tenant-scoped (`university_id`), uang `decimal(14,2)`, status
 * `string` + PHP enum. Data transaksi tidak pernah dihapus: koreksi lewat
 * status/entri pembalik. Penyederhanaan terhadap rancangan dicatat di
 * docs/keu/catatan-implementasi.md.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = [
        'finance_clearance_rules', 'collection_notes', 'finance_sequences', 'refunds', 'reconciliation_items',
        'reconciliation_batches', 'student_deposit_entries', 'student_deposit_accounts', 'cashier_sessions', 'payment_proofs',
        'payment_callbacks', 'payment_transactions', 'payment_channels', 'voucher_redemptions', 'vouchers',
        'financial_aid_awards', 'discount_policies', 'penalty_accruals', 'penalty_rules', 'invoice_installments',
        'installment_requests', 'installment_plans', 'invoice_adjustments', 'invoice_items', 'billing_run_items',
        'billing_runs', 'ukt_change_requests', 'student_ukt_assignments', 'fee_rates', 'fee_rate_tables', 'ukt_groups',
        'fee_components',
    ];

    public function up(): void
    {
        Schema::create('fee_components', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('category', 30);
            $table->string('billing_frequency', 20)->default('per_term');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('installable')->default(true);
            $table->boolean('discountable')->default(true);
            $table->boolean('refundable')->default(false);
            $table->boolean('counts_for_clearance')->default(true);
            $table->string('pddikti_category', 20)->nullable();
            $table->string('gl_account_code', 30)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('ukt_groups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 100);
            $table->unsignedSmallInteger('level')->default(1);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('fee_rate_tables', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->string('academic_year', 9);
            $table->foreignUlid('effective_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignUlid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'code', 'version']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('fee_rates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('fee_rate_table_id')->constrained('fee_rate_tables')->cascadeOnDelete();
            $table->foreignUlid('fee_component_id')->constrained('fee_components')->restrictOnDelete();
            $table->foreignUlid('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->unsignedSmallInteger('admission_year_from')->nullable();
            $table->unsignedSmallInteger('admission_year_to')->nullable();
            $table->string('admission_path_code', 30)->nullable();
            $table->string('study_system_code', 20)->nullable();
            $table->foreignUlid('ukt_group_id')->nullable()->constrained('ukt_groups')->nullOnDelete();
            $table->unsignedTinyInteger('semester_level_from')->nullable();
            $table->unsignedTinyInteger('semester_level_to')->nullable();
            $table->string('calc_basis', 20)->default('flat');
            $table->decimal('amount', 14, 2);
            $table->unsignedTinyInteger('min_credits')->nullable();
            $table->unsignedTinyInteger('max_credits')->nullable();
            $table->unsignedSmallInteger('specificity_score')->default(0);
            $table->timestampsTz();
            $table->index(['fee_rate_table_id', 'fee_component_id']);
        });

        Schema::create('student_ukt_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('ukt_group_id')->constrained('ukt_groups')->restrictOnDelete();
            $table->foreignUlid('effective_from_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->text('basis');
            $table->string('decree_number', 60)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignUlid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('superseded_at')->nullable();
            $table->timestampsTz();
            $table->index(['student_id', 'status']);
        });

        Schema::create('ukt_change_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('current_group_id')->nullable()->constrained('ukt_groups')->nullOnDelete();
            $table->foreignUlid('requested_group_id')->nullable()->constrained('ukt_groups')->nullOnDelete();
            $table->text('reason');
            $table->foreignUlid('evidence_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('status', 20)->default('submitted');
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('decided_group_id')->nullable()->constrained('ukt_groups')->nullOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignUlid('effective_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->ulid('assignment_id')->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'status']);
        });

        Schema::create('billing_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('run_number', 30);
            $table->foreignUlid('academic_term_id')->constrained('academic_terms')->restrictOnDelete();
            $table->string('bill_group', 30)->default('registration');
            $table->json('fee_component_ids');
            $table->json('scope')->nullable();
            $table->date('due_date');
            $table->string('status', 20)->default('simulated');
            $table->json('totals')->nullable();
            $table->json('posted_totals')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->timestampsTz();
            $table->unique(['university_id', 'run_number']);
        });

        Schema::create('billing_run_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('billing_run_id')->constrained('billing_runs')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('status', 20);
            $table->string('skip_reason', 40)->nullable();
            $table->decimal('gross_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->ulid('invoice_id')->nullable();
            $table->json('lines')->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestampsTz();
            $table->unique(['billing_run_id', 'student_id']);
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('line_type', 20);
            $table->foreignUlid('fee_component_id')->nullable()->constrained('fee_components')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_amount', 14, 2)->default(0);
            $table->decimal('amount', 14, 2);
            $table->string('source_type', 40)->nullable();
            $table->ulid('source_id')->nullable();
            $table->string('active_key', 160)->nullable()->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
            $table->index(['invoice_id']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('invoice_adjustments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('adjustment_number', 30);
            $table->foreignUlid('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 14, 2);
            $table->string('reason_code', 40);
            $table->text('reason_note');
            $table->foreignUlid('evidence_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('status', 20)->default('pending_approval');
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampTz('applied_at')->nullable();
            $table->timestampsTz();
            $table->unique(['university_id', 'adjustment_number']);
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('installment_plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->json('bill_groups');
            $table->json('terms');
            $table->decimal('min_first_payment', 14, 2)->nullable();
            $table->boolean('auto_approve')->default(true);
            $table->boolean('require_no_arrears')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('installment_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUlid('installment_plan_id')->nullable()->constrained('installment_plans')->nullOnDelete();
            $table->json('terms');
            $table->string('kind', 20)->default('new');
            $table->string('status', 20)->default('submitted');
            $table->string('plan_status', 20)->nullable();
            $table->unsignedSmallInteger('schedule_version')->default(1);
            $table->text('reason')->nullable();
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampsTz();
            $table->index(['invoice_id', 'status']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('invoice_installments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUlid('installment_request_id')->nullable()->constrained('installment_requests')->nullOnDelete();
            $table->unsignedTinyInteger('seq');
            $table->unsignedSmallInteger('schedule_version')->default(1);
            $table->decimal('amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->date('due_date');
            $table->string('status', 20)->default('scheduled');
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('superseded_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestampsTz();
            $table->index(['invoice_id', 'schedule_version']);
            $table->index(['university_id', 'status', 'due_date']);
        });

        Schema::create('penalty_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->json('bill_groups');
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->string('calc_type', 20);
            $table->string('period_unit', 10)->default('once');
            $table->decimal('rate_percent', 8, 4)->nullable();
            $table->decimal('flat_amount', 14, 2)->nullable();
            $table->decimal('cap_amount', 14, 2)->nullable();
            $table->decimal('cap_percent', 6, 2)->nullable();
            $table->unsignedInteger('max_periods')->nullable();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('decree_reference', 120)->nullable();
            $table->foreignUlid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('penalty_accruals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUlid('invoice_installment_id')->nullable()->constrained('invoice_installments')->nullOnDelete();
            $table->foreignUlid('penalty_rule_id')->constrained('penalty_rules')->restrictOnDelete();
            $table->string('active_key', 120)->unique();
            $table->date('overdue_from');
            $table->date('last_calculated_on')->nullable();
            $table->unsignedInteger('periods_counted')->default(0);
            $table->decimal('accrued_amount', 14, 2)->default(0);
            $table->string('status', 20)->default('accruing');
            $table->ulid('penalty_invoice_id')->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'status']);
        });

        Schema::create('discount_policies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('kind', 20);
            $table->string('funder_name', 150)->nullable();
            $table->foreignUlid('scholarship_id')->nullable()->constrained('scholarships')->nullOnDelete();
            $table->string('calc_type', 20);
            $table->decimal('value', 14, 2)->default(0);
            $table->decimal('max_amount', 14, 2)->nullable();
            $table->json('fee_component_ids')->nullable();
            $table->unsignedSmallInteger('priority')->default(2);
            $table->boolean('stackable')->default(true);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('financial_aid_awards', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('discount_policy_id')->constrained('discount_policies')->restrictOnDelete();
            $table->string('calc_type', 20);
            $table->decimal('value', 14, 2)->default(0);
            $table->decimal('max_amount', 14, 2)->nullable();
            $table->json('fee_component_ids')->nullable();
            $table->foreignUlid('start_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->foreignUlid('end_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->string('status', 20)->default('pending_approval');
            $table->text('basis');
            $table->foreignUlid('evidence_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignUlid('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoke_reason', 255)->nullable();
            $table->timestampsTz();
            $table->index(['student_id', 'status']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('vouchers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 14, 2);
            $table->decimal('max_discount', 14, 2)->nullable();
            $table->json('bill_groups')->nullable();
            $table->json('scope')->nullable();
            $table->unsignedInteger('quota_total')->nullable();
            $table->unsignedInteger('quota_used')->default(0);
            $table->unsignedSmallInteger('per_student_limit')->default(1);
            $table->decimal('min_invoice_amount', 14, 2)->nullable();
            $table->timestampTz('valid_from');
            $table->timestampTz('valid_until');
            $table->string('status', 20)->default('active');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('voucher_redemptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('voucher_id')->constrained('vouchers')->restrictOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('discount_amount', 14, 2);
            $table->string('status', 20)->default('applied');
            $table->foreignUlid('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reversed_at')->nullable();
            $table->foreignUlid('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['voucher_id', 'student_id']);
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('payment_channels', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->string('category', 20);
            $table->string('provider', 30);
            $table->string('method', 20);
            $table->string('bank_code', 20)->nullable();
            $table->decimal('fee_amount', 14, 2)->default(0);
            $table->string('fee_bearer', 20)->default('campus');
            $table->unsignedInteger('expiry_minutes')->default(1440);
            $table->text('instructions')->nullable();
            $table->string('status', 20)->default('inactive');
            $table->boolean('counts_toward_quota')->default(true);
            $table->string('environment', 20)->default('production');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('transaction_number', 30);
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('payer_name', 150)->nullable();
            $table->foreignUlid('payment_channel_id')->nullable()->constrained('payment_channels')->nullOnDelete();
            $table->string('provider', 30);
            $table->string('method', 20);
            $table->string('target_type', 20);
            $table->ulid('target_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->decimal('channel_fee', 14, 2)->default(0);
            $table->char('currency', 3)->default('IDR');
            $table->string('status', 30)->default('created');
            $table->string('idempotency_key', 100)->nullable();
            $table->string('provider_reference', 100)->nullable();
            $table->string('va_number', 40)->nullable();
            $table->text('qr_string')->nullable();
            $table->string('redirect_url', 500)->nullable();
            $table->json('instructions')->nullable();
            $table->unsignedSmallInteger('unique_code')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->ulid('cashier_session_id')->nullable();
            $table->string('receipt_number', 40)->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->string('reconciliation_status', 20)->default('unreconciled');
            $table->timestampTz('reconciled_at')->nullable();
            $table->decimal('refunded_amount', 14, 2)->default(0);
            $table->string('failure_reason', 255)->nullable();
            $table->boolean('needs_resolution')->default(false);
            $table->timestampTz('reversed_at')->nullable();
            $table->string('reversal_reason', 255)->nullable();
            $table->unsignedSmallInteger('recheck_attempts')->default(0);
            $table->timestampTz('next_recheck_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestampsTz();
            $table->unique(['university_id', 'transaction_number']);
            $table->unique(['university_id', 'idempotency_key']);
            $table->unique(['provider', 'provider_reference']);
            $table->unique(['university_id', 'receipt_number']);
            $table->index(['student_id', 'status']);
            $table->index(['university_id', 'status', 'paid_at']);
            $table->index(['status', 'expires_at']);
            $table->index(['cashier_session_id']);
        });

        Schema::create('payment_callbacks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->nullable()->constrained('universities')->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('provider_event_id', 150);
            $table->foreignUlid('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
            $table->boolean('signature_valid');
            $table->json('payload');
            $table->timestampTz('received_at');
            $table->timestampTz('processed_at')->nullable();
            $table->string('processing_result', 30)->nullable();
            $table->text('error_message')->nullable();
            $table->timestampsTz();
            $table->unique(['provider', 'provider_event_id']);
        });

        Schema::create('payment_proofs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('payment_transaction_id')->unique()->constrained('payment_transactions')->cascadeOnDelete();
            $table->foreignUlid('file_upload_id')->constrained('file_uploads')->restrictOnDelete();
            $table->string('sender_bank', 60);
            $table->string('sender_name', 150);
            $table->date('transfer_date');
            $table->decimal('transferred_amount', 14, 2);
            $table->string('checksum', 64)->nullable();
            $table->string('status', 20)->default('submitted');
            $table->string('reason_code', 40)->nullable();
            $table->text('note')->nullable();
            $table->boolean('duplicate_warning')->default(false);
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->index(['university_id', 'status']);
            $table->index(['checksum']);
        });

        Schema::create('cashier_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('session_number', 30);
            $table->foreignUlid('cashier_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('opened_at');
            $table->decimal('opening_float', 14, 2)->default(0);
            $table->timestampTz('closed_at')->nullable();
            $table->decimal('system_total', 14, 2)->default(0);
            $table->decimal('counted_total', 14, 2)->nullable();
            $table->decimal('difference', 14, 2)->nullable();
            $table->string('deposit_reference', 100)->nullable();
            $table->foreignUlid('deposit_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->text('note')->nullable();
            $table->timestampsTz();
            $table->unique(['university_id', 'session_number']);
            $table->index(['cashier_id', 'status']);
        });

        Schema::create('student_deposit_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('held_amount', 14, 2)->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestampsTz();
            $table->unique(['university_id', 'student_id']);
        });

        Schema::create('student_deposit_entries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('account_id')->constrained('student_deposit_accounts')->cascadeOnDelete();
            $table->string('entry_type', 30);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->decimal('held_after', 14, 2)->default(0);
            $table->string('reference_type', 40)->nullable();
            $table->ulid('reference_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['account_id', 'created_at']);
        });

        Schema::create('reconciliation_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('batch_number', 30);
            $table->string('source', 30);
            $table->foreignUlid('payment_channel_id')->nullable()->constrained('payment_channels')->nullOnDelete();
            $table->date('statement_date');
            $table->foreignUlid('source_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('matched_items')->default(0);
            $table->unsignedInteger('exception_items')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['university_id', 'batch_number']);
        });

        Schema::create('reconciliation_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('batch_id')->constrained('reconciliation_batches')->cascadeOnDelete();
            $table->foreignUlid('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
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

        Schema::create('refunds', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('refund_number', 30);
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('source_type', 20)->default('deposit');
            $table->decimal('amount', 14, 2);
            $table->string('reason_code', 40);
            $table->text('reason_note');
            $table->string('beneficiary_bank', 60);
            $table->text('beneficiary_account');
            $table->text('beneficiary_name');
            $table->string('beneficiary_relation', 20)->default('self');
            $table->string('status', 20)->default('pending_approval');
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignUlid('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('executed_at')->nullable();
            $table->string('execution_reference', 100)->nullable();
            $table->foreignUlid('proof_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('failure_reason', 255)->nullable();
            $table->timestampsTz();
            $table->unique(['university_id', 'refund_number']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('finance_sequences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('scope', 30);
            $table->string('key', 40);
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestampsTz();
            $table->unique(['university_id', 'scope', 'key']);
        });

        Schema::create('collection_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->text('note');
            $table->decimal('promise_amount', 14, 2)->nullable();
            $table->date('promise_date')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['student_id', 'created_at']);
        });

        Schema::create('finance_clearance_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->json('services');
            $table->string('requirement', 30);
            $table->json('bill_groups')->nullable();
            $table->string('terms', 30)->default('current');
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->decimal('min_outstanding_amount', 14, 2)->default(1);
            $table->boolean('include_penalties')->default(false);
            $table->foreignUlid('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['university_id', 'code']);
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};
