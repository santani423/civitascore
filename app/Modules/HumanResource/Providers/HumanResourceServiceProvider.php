<?php

namespace Modules\HumanResource\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Academic\Models\Lecturer;
use Modules\ApprovalWorkflow\Contracts\ContextualApproverResolver;
use Modules\HumanResource\Support\HrApproverResolver;
use Modules\HumanResource\Console\HrDailyMaintenanceCommand;
use Modules\HumanResource\Listeners\SyncHrRequestDecision;
use Modules\HumanResource\Observers\LecturerObserver;
use Modules\HumanResource\Policies\HrEmployeePolicy;

/**
 * Wiring Modul SDM yang tidak bisa ditemukan otomatis oleh konvensi
 * modul (route & migration sudah dimuat routes/api.php dan
 * ModuleServiceProvider).
 */
class HumanResourceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Menimpa NullContextualApproverResolver (AppServiceProvider):
        // approver jabatan/atasan langsung/kepala unit di ApprovalWorkflow
        // diresolusi dari data kepegawaian.
        $this->app->bind(ContextualApproverResolver::class, HrApproverResolver::class);
    }

    public function boot(): void
    {
        Event::subscribe(SyncHrRequestDecision::class);
        Lecturer::observe(LecturerObserver::class);

        // Model Employee sudah punya policy milik Modul Akademik
        // (EmployeePolicy, berbasis employees.read) — otorisasi Modul SDM
        // didaftarkan sebagai ability terpisah, bukan menimpanya.
        Gate::define('hr.employees.viewAny', [HrEmployeePolicy::class, 'viewAny']);
        Gate::define('hr.employees.view', [HrEmployeePolicy::class, 'view']);
        Gate::define('hr.employees.create', [HrEmployeePolicy::class, 'create']);
        Gate::define('hr.employees.update', [HrEmployeePolicy::class, 'update']);
        Gate::define('hr.employees.delete', [HrEmployeePolicy::class, 'delete']);
        Gate::define('hr.employees.export', [HrEmployeePolicy::class, 'export']);

        if ($this->app->runningInConsole()) {
            $this->commands([HrDailyMaintenanceCommand::class]);
        }
    }
}
