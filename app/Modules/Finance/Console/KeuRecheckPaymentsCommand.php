<?php

namespace Modules\Finance\Console;

use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Payments\GatewayRegistry;
use Modules\Finance\Services\OnlinePaymentService;
use Throwable;

/** Polling cadangan transaksi online yang belum final (callback hilang/terlambat, FR-PON-04). */
class KeuRecheckPaymentsCommand extends Command
{
    protected $signature = 'keu:recheck-payments';

    protected $description = 'Cek ulang status transaksi online pending ke penyedia.';

    public function handle(OnlinePaymentService $online, GatewayRegistry $gateways, TenantContext $tenant): int
    {
        if ($gateways->names() === []) {
            return self::SUCCESS;
        }

        PaymentTransaction::query()->withoutGlobalScopes()->where('status', TransactionStatus::Pending)->where('method', PaymentMethod::Online)
            ->whereIn('provider', $gateways->names())->where(fn ($q) => $q->whereNull('next_recheck_at')->orWhere('next_recheck_at', '<=', now()))
            ->limit(200)->get()
            ->each(function (PaymentTransaction $tx) use ($online, $tenant): void {
                try {
                    $tenant->setUniversityId($tx->university_id);
                    $online->recheck($tx);
                } catch (Throwable $e) {
                    report($e);
                }
            });

        $tenant->setUniversityId(null);

        return self::SUCCESS;
    }
}
