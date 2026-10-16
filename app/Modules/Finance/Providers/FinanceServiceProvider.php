<?php

namespace Modules\Finance\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Academic\Events\KrsSubmissionApproved;
use Modules\Finance\Console\KeuDailyCommand;
use Modules\Finance\Console\KeuInstallCommand;
use Modules\Finance\Console\KeuRecheckPaymentsCommand;
use Modules\Finance\Events\StudentFinanceChanged;
use Modules\Finance\Listeners\BillCreditsOnKrsApproval;
use Modules\Finance\Listeners\ReevaluateFinancialClearance;
use Modules\Finance\Payments\GatewayRegistry;
use Modules\Finance\Payments\MidtransGateway;
use Modules\Finance\Support\KeuSettings;

/**
 * Modul KEU — Administrasi Keuangan Mahasiswa. Adapter penyedia pembayaran
 * hanya didaftarkan bila kredensialnya ada (config/keu.php); tanpa itu
 * pembayaran online tidak tersedia dan sistem tidak berpura-pura terhubung.
 */
class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(KeuSettings::class);
        $this->app->singleton(GatewayRegistry::class, function (): GatewayRegistry {
            $registry = new GatewayRegistry;
            $midtrans = config('keu.gateways.midtrans');

            if (! empty($midtrans['server_key'])) {
                $registry->register(new MidtransGateway((string) $midtrans['server_key'], (bool) $midtrans['is_production'], (int) ($midtrans['timeout_seconds'] ?? 10)));
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Views', 'finance');
        Event::listen(StudentFinanceChanged::class, ReevaluateFinancialClearance::class);
        Event::listen(KrsSubmissionApproved::class, BillCreditsOnKrsApproval::class);

        if ($this->app->runningInConsole()) {
            $this->commands([KeuInstallCommand::class, KeuDailyCommand::class, KeuRecheckPaymentsCommand::class]);
        }
    }
}
