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
use Modules\Finance\Payments\XenditGateway;
use Modules\Finance\Support\KeuSettings;

/**
 * Modul KEU — Administrasi Keuangan Mahasiswa. Adapter penyedia pembayaran
 * (Xendit) hanya didaftarkan bila kredensialnya ada (config/services.php); tanpa itu
 * pembayaran online tidak tersedia dan sistem tidak berpura-pura terhubung.
 */
class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(KeuSettings::class);
        $this->app->singleton(GatewayRegistry::class, function (): GatewayRegistry {
            $registry = new GatewayRegistry;
            $xendit = config('services.xendit');

            if (! empty($xendit['secret_key'])) {
                $registry->register(new XenditGateway((string) $xendit['secret_key'], (string) ($xendit['webhook_token'] ?? ''), (string) $xendit['base_url'], (int) $xendit['timeout']));
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
