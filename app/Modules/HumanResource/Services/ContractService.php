<?php

namespace Modules\HumanResource\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\ContractStatus;
use Modules\HumanResource\Models\EmployeeContract;
use Modules\HumanResource\Services\Concerns\SavesRecordWithFiles;

class ContractService
{
    use SavesRecordWithFiles;

    /** Ambang pengingat kontrak (hari sebelum berakhir), dari yang terjauh. */
    public const REMINDER_THRESHOLDS = [90, 60, 30, 7];

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Employee $employee, array $data, User $actor, ?EmployeeContract $contract = null): EmployeeContract
    {
        $status = $contract->status ?? ContractStatus::Active;

        if ($status === ContractStatus::Active) {
            $this->assertNoOverlap($employee, $data['start_date'], $data['end_date'] ?? null, $contract);
        }

        return DB::transaction(function () use ($employee, $data, $actor, $contract): EmployeeContract {
            $contract ??= new EmployeeContract([
                'employee_id' => $employee->id,
                'university_id' => $employee->university_id,
                'status' => ContractStatus::Active,
            ]);

            // Tanggal akhir diubah (mis. diperpanjang) → pengingat dihitung
            // ulang dari awal.
            if ($contract->exists && ($data['end_date'] ?? null) !== $contract->end_date?->toDateString()) {
                $data['last_reminder_days'] = null;
            }

            return $this->saveWithFiles($contract, $data, ['document_file_id'], $actor);
        });
    }

    public function terminate(EmployeeContract $contract, ?string $notes): EmployeeContract
    {
        if ($contract->status !== ContractStatus::Active) {
            throw new ConflictException('Hanya kontrak aktif yang dapat diakhiri.');
        }

        $contract->update([
            'status' => ContractStatus::Terminated,
            'terminated_at' => now(),
            'notes' => $notes ?? $contract->notes,
        ]);

        return $contract;
    }

    /**
     * Kontrak aktif yang tanggal akhirnya sudah lewat → expired. Dipanggil
     * command harian hr:daily-maintenance.
     */
    public function expireDue(): int
    {
        $expired = 0;

        EmployeeContract::query()
            ->where('status', ContractStatus::Active)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', CarbonImmutable::today())
            ->each(function (EmployeeContract $contract) use (&$expired): void {
                $contract->update(['status' => ContractStatus::Expired]);
                $expired++;
            });

        return $expired;
    }

    /**
     * Ambang pengingat yang sudah terlewati tapi belum dikirim — null kalau
     * tidak ada yang perlu dikirim hari ini. Hanya ambang terkecil yang
     * sudah tercapai yang dikirim (tidak mengirim 90/60/30 sekaligus untuk
     * kontrak yang baru diinput 5 hari sebelum berakhir).
     */
    public function dueReminderThreshold(EmployeeContract $contract): ?int
    {
        $daysLeft = $contract->daysRemaining();

        if ($contract->status !== ContractStatus::Active || $daysLeft === null || $daysLeft < 0) {
            return null;
        }

        $reached = array_values(array_filter(self::REMINDER_THRESHOLDS, fn (int $threshold): bool => $daysLeft <= $threshold));

        if ($reached === []) {
            return null;
        }

        $threshold = min($reached);

        return $contract->last_reminder_days === null || $threshold < $contract->last_reminder_days ? $threshold : null;
    }

    private function assertNoOverlap(Employee $employee, string $start, ?string $end, ?EmployeeContract $except): void
    {
        $overlaps = EmployeeContract::query()
            ->where('employee_id', $employee->id)
            ->where('status', ContractStatus::Active)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->id))
            ->where(function ($query) use ($start): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $start);
            })
            ->when($end !== null, fn ($query) => $query->whereDate('start_date', '<=', $end))
            ->exists();

        if ($overlaps) {
            throw new ConflictException('Periode kontrak bertumpang tindih dengan kontrak aktif lain milik pegawai ini. Akhiri kontrak sebelumnya terlebih dahulu.');
        }
    }
}
