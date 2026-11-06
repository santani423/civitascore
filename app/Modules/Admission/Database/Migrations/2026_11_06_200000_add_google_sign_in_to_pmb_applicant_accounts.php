<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Masuk/daftar dengan Google untuk akun pendaftar PMB. Hanya menambah kolom
 * pada `pmb_applicant_accounts`; akun lama tetap utuh dan otomatis bernilai
 * `auth_provider = email`, `has_password = true`.
 *
 * - google_id        klaim `sub` ID token Google (stabil, bukan email); unik per tenant
 *                    seperti email, sehingga satu akun Google dapat mendaftar di kampus lain.
 * - auth_provider    metode saat akun dibuat: `email` (formulir/loket) atau `google`.
 * - has_password     false untuk akun yang dibuat lewat Google (sandinya acak dan tidak
 *                    diketahui siapa pun) sampai pendaftar mengatur sandi via "Lupa kata sandi".
 * - google_linked_at kapan akun Google dihubungkan.
 * - avatar_url       foto profil Google (opsional, hanya untuk tampilan).
 *
 * Setiap kolom dicek dulu agar migrasi aman dijalankan ulang bila sempat gagal
 * di tengah (MySQL tidak membungkus DDL dalam transaksi).
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'google_id' => fn (Blueprint $t) => $t->string('google_id', 64)->nullable(),
            'auth_provider' => fn (Blueprint $t) => $t->string('auth_provider', 20)->default('email'),
            'has_password' => fn (Blueprint $t) => $t->boolean('has_password')->default(true),
            'google_linked_at' => fn (Blueprint $t) => $t->timestampTz('google_linked_at')->nullable(),
            'avatar_url' => fn (Blueprint $t) => $t->string('avatar_url', 500)->nullable(),
        ];

        foreach ($columns as $name => $define) {
            if (! Schema::hasColumn('pmb_applicant_accounts', $name)) {
                Schema::table('pmb_applicant_accounts', fn (Blueprint $table) => $define($table));
            }
        }

        if (! Schema::hasIndex('pmb_applicant_accounts', ['university_id', 'google_id'], 'unique')) {
            Schema::table('pmb_applicant_accounts', function (Blueprint $table): void {
                $table->unique(['university_id', 'google_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('pmb_applicant_accounts', ['university_id', 'google_id'], 'unique')) {
            Schema::table('pmb_applicant_accounts', function (Blueprint $table): void {
                $table->dropUnique(['university_id', 'google_id']);
            });
        }

        foreach (['avatar_url', 'google_linked_at', 'has_password', 'auth_provider', 'google_id'] as $column) {
            if (Schema::hasColumn('pmb_applicant_accounts', $column)) {
                Schema::table('pmb_applicant_accounts', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
