<?php

namespace Modules\Admission\Support;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admission\Models\Sequence;
use RuntimeException;

/**
 * Penomoran ber-lock (10 §4.9 `pmb_sequences`, contoh SQL 10 §8). WAJIB
 * dipanggil di dalam transaksi pemakai: bila transaksi rollback, nomor ikut
 * kembali sehingga tidak ada nomor terbuang (K.6).
 */
class SequenceGenerator
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function next(string $scope, string $key, ?string $universityId = null): int
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('SequenceGenerator::next() harus dipanggil di dalam transaksi.');
        }

        $universityId ??= $this->tenant->universityId();

        if ($universityId === null) {
            throw new RuntimeException('Tenant belum ter-resolve untuk penomoran.');
        }

        DB::table('pmb_sequences')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'university_id' => $universityId,
            'scope' => $scope,
            'key' => $key,
            'last_value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = Sequence::query()->withoutGlobalScopes()
            ->where('university_id', $universityId)->where('scope', $scope)->where('key', $key)
            ->lockForUpdate()->firstOrFail();

        $next = $row->last_value + 1;
        $row->forceFill(['last_value' => $next])->save();

        return $next;
    }

    public static function pad(int $value, int $width): string
    {
        return str_pad((string) $value, $width, '0', STR_PAD_LEFT);
    }
}
