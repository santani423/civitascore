<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Asal tagihan & jejak transaksi gateway (docs/pmb/10 §6, KD-03): posting
 * tagihan PMB ke Finance idempoten lewat unique (source_type, source_id).
 * Additive & nullable — tagihan lama tetap valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('source_type', 30)->nullable()->after('due_date');
            $table->ulid('source_id')->nullable()->after('source_type');
            $table->unique(['source_type', 'source_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('reference', 100)->nullable()->after('method');
            $table->string('channel', 30)->nullable()->after('reference');
        });
    }

    public function down(): void
    {
        if (DB::table('invoices')->whereNotNull('source_type')->exists()) {
            throw new RuntimeException('invoices.source_type sudah berisi data; rollback dibatalkan.');
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['reference', 'channel']);
        });
    }
};
