<?php

namespace Modules\HumanResource\Console;

use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Modules\HumanResource\Enums\ContractStatus;
use Modules\HumanResource\Models\EmployeeContract;
use Modules\HumanResource\Services\ContractService;
use Modules\HumanResource\Services\HrNotificationService;
use Modules\HumanResource\Services\TransferService;
use Modules\Tenancy\Models\University;

/**
 * Pekerjaan harian Modul SDM, per universitas:
 *  1. kontrak aktif yang sudah lewat tanggal akhir → expired,
 *  2. mutasi terjadwal yang tanggal efektifnya tiba → diterapkan,
 *  3. pengingat kontrak 90/60/30/7 hari sebelum berakhir → notifikasi ke
 *     Bagian SDM & pegawai bersangkutan (idempotent lewat
 *     employee_contracts.last_reminder_days — aman dijalankan berulang).
 */
class HrDailyMaintenanceCommand extends Command
{
    protected $signature = 'hr:daily-maintenance';

    protected $description = 'Perbarui status kontrak, terapkan mutasi terjadwal, dan kirim pengingat kontrak SDM';

    public function handle(
        TenantContext $tenant,
        ContractService $contracts,
        TransferService $transfers,
        HrNotificationService $notifications,
    ): int {
        foreach (University::query()->get() as $university) {
            $tenant->setUniversityId($university->id);

            $expired = $contracts->expireDue();
            $applied = $transfers->applyDue();
            $reminded = $this->sendContractReminders($contracts, $notifications);

            $this->line("{$university->name}: {$expired} kontrak berakhir, {$applied} mutasi diterapkan, {$reminded} pengingat kontrak.");
        }

        $tenant->setUniversityId(null);

        return self::SUCCESS;
    }

    private function sendContractReminders(ContractService $contracts, HrNotificationService $notifications): int
    {
        $sent = 0;

        EmployeeContract::query()
            ->where('status', ContractStatus::Active)
            ->whereNotNull('end_date')
            ->with('employee.user')
            ->each(function (EmployeeContract $contract) use ($contracts, $notifications, &$sent): void {
                $threshold = $contracts->dueReminderThreshold($contract);

                if ($threshold === null || $contract->employee === null) {
                    return;
                }

                $placeholders = [
                    'contract_number' => $contract->contract_number,
                    'employee' => $contract->employee->name,
                    'days' => (string) $contract->daysRemaining(),
                    'end_date' => (string) $contract->end_date?->format('d/m/Y'),
                ];

                $notifications->notifyHrAdministrators('hr.contract_expiring', $placeholders);
                $notifications->notifyEmployee($contract->employee, 'hr.contract_expiring', $placeholders);

                $contract->update(['last_reminder_days' => $threshold]);
                $sent++;
            });

        return $sent;
    }
}
