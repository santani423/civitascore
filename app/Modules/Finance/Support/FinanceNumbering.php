<?php

namespace Modules\Finance\Support;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FinanceSequence;

/**
 * Penomoran dokumen KEU dengan row lock per (tenant, scope, key) — dipanggil
 * di dalam transaksi pembuatan dokumen sehingga rollback tidak menghabiskan
 * nomor (rancangan KEU §9.6). Kuitansi berurutan tanpa celah per bulan.
 */
class FinanceNumbering
{
    public const FORMATS = [
        'invoice' => ['TGH', 7],
        'receipt' => ['KWT', 6],
        'transaction' => ['TRX', 6],
        'adjustment' => ['ADJ', 6],
        'refund' => ['RFD', 5],
        'billing_run' => ['BR', 3],
        'cashier_session' => ['KAS', 4],
        'reconciliation' => ['REK', 4],
    ];

    public function __construct(private readonly TenantContext $tenant) {}

    public function next(string $scope, ?string $key = null, ?string $universityId = null): string
    {
        [$prefix, $pad] = self::FORMATS[$scope];
        $key ??= now()->format($scope === 'receipt' || $scope === 'transaction' ? 'Ym' : 'Y');
        $universityId ??= $this->tenant->universityId();

        $value = DB::transaction(function () use ($scope, $key, $universityId): int {
            $sequence = FinanceSequence::query()->withoutGlobalScopes()
                ->where('university_id', $universityId)->where('scope', $scope)->where('key', $key)
                ->lockForUpdate()->first();

            if ($sequence === null) {
                FinanceSequence::query()->create(['university_id' => $universityId, 'scope' => $scope, 'key' => $key, 'next_value' => 2]);

                return 1;
            }

            $current = $sequence->next_value;
            $sequence->forceFill(['next_value' => $current + 1])->save();

            return $current;
        });

        return sprintf('%s-%s-%s', $prefix, $key, str_pad((string) $value, $pad, '0', STR_PAD_LEFT));
    }
}
