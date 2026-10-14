<?php

namespace Modules\Admission\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * MySQL tidak menjalankan DDL di dalam transaksi: bila migrasi gagal di
 * tengah jalan, tabel yang sempat dibuat tertinggal tanpa tercatat di
 * `migrations`, sehingga `migrate` berikutnya gagal "table already exists".
 *
 * Sebelum membuat tabel, migrasi PMB memanggil helper ini untuk membersihkan
 * sisa percobaan gagal. Hanya tabel KOSONG yang dihapus; bila ada tabel yang
 * berisi data, migrasi dihentikan dengan pesan jelas tanpa menghapus apa pun.
 */
final class MigrationRecovery
{
    /**
     * @param  array<int, string>  $tables  urutan pembuatan (dihapus terbalik)
     */
    public static function clearFailedAttempt(array $tables): void
    {
        $leftovers = array_values(array_filter($tables, fn (string $table): bool => Schema::hasTable($table)));

        if ($leftovers === []) {
            return;
        }

        $withData = array_values(array_filter($leftovers, fn (string $table): bool => DB::table($table)->exists()));

        if ($withData !== []) {
            throw new RuntimeException('Tabel '.implode(', ', $withData).' sudah ada dan berisi data, padahal migrasi ini belum tercatat. '
                .'Migrasi dihentikan agar data tidak hilang; periksa tabel tersebut dan tabel `migrations` secara manual.');
        }

        Schema::withoutForeignKeyConstraints(function () use ($leftovers): void {
            foreach (array_reverse($leftovers) as $table) {
                Schema::drop($table);
            }
        });
    }
}
