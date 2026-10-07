<?php

namespace Modules\HumanResource\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\TransferStatus;
use Modules\HumanResource\Models\EmployeePosition;
use Modules\HumanResource\Models\EmployeeTransfer;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Models\Position;
use Modules\HumanResource\Models\WorkUnit;
use Modules\HumanResource\Services\Concerns\SavesRecordWithFiles;

/**
 * Penempatan & mutasi. Unit/jabatan asal disalin (snapshot) saat mutasi
 * dicatat. Mutasi dengan tanggal efektif hari ini atau lebih awal langsung
 * diterapkan; yang di masa depan menunggu command harian.
 */
class TransferService
{
    use SavesRecordWithFiles;

    public function __construct(
        private readonly EmployeeService $employees,
        private readonly HrRequestService $requests,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Employee $employee, array $data, User $actor): EmployeeTransfer
    {
        if (empty($data['to_work_unit_id']) && empty($data['to_position_id'])) {
            throw ValidationException::withMessages(['to_work_unit_id' => 'Isi unit kerja baru dan/atau jabatan baru.']);
        }

        $toUnit = ! empty($data['to_work_unit_id']) ? WorkUnit::query()->whereKey($data['to_work_unit_id'])->firstOrFail() : null;
        $toPosition = ! empty($data['to_position_id']) ? Position::query()->whereKey($data['to_position_id'])->firstOrFail() : null;
        $hrRequest = ! empty($data['hr_request_id']) ? HrRequest::query()->whereKey($data['hr_request_id'])->firstOrFail() : null;
        unset($data['hr_request_id']);

        return DB::transaction(function () use ($employee, $data, $actor, $toUnit, $toPosition, $hrRequest): EmployeeTransfer {
            $employee->loadMissing('workUnit', 'jobPosition');

            $transfer = new EmployeeTransfer([
                'employee_id' => $employee->id,
                'university_id' => $employee->university_id,
                'from_work_unit_id' => $employee->work_unit_id,
                'from_work_unit_name' => $employee->workUnit->name ?? $employee->unit_kerja,
                'from_position_id' => $employee->position_id,
                'from_position_name' => $employee->jobPosition->name ?? $employee->position,
                'to_work_unit_name' => $toUnit?->name,
                'to_position_name' => $toPosition?->name,
                'status' => TransferStatus::Scheduled,
                'created_by' => $actor->id,
            ]);

            $this->saveWithFiles($transfer, $data, ['document_file_id'], $actor);

            if ($transfer->effective_date->lte(CarbonImmutable::today())) {
                $this->apply($transfer);
            }

            if ($hrRequest !== null) {
                $this->requests->markProcessed($hrRequest, $actor);
            }

            return $transfer->refresh();
        });
    }

    public function apply(EmployeeTransfer $transfer): void
    {
        if ($transfer->status !== TransferStatus::Scheduled) {
            throw new ConflictException('Mutasi ini sudah diterapkan atau dibatalkan.');
        }

        DB::transaction(function () use ($transfer): void {
            $employee = $transfer->employee;

            if ($employee === null) {
                return;
            }

            if ($transfer->to_work_unit_id !== null) {
                $employee->work_unit_id = $transfer->to_work_unit_id;
            }

            if ($transfer->to_position_id !== null && $transfer->to_position_id !== $employee->position_id) {
                $position = Position::query()->findOrFail($transfer->to_position_id);

                EmployeePosition::query()
                    ->where('employee_id', $employee->id)
                    ->where('position_type', $position->type->value)
                    ->where('is_current', true)
                    ->get()
                    ->each(fn (EmployeePosition $previous) => $previous->update([
                        'is_current' => false,
                        'end_date' => $previous->end_date ?? $transfer->effective_date->subDay()->toDateString(),
                    ]));

                EmployeePosition::query()->create([
                    'university_id' => $employee->university_id,
                    'employee_id' => $employee->id,
                    'position_id' => $position->id,
                    'position_name' => $position->name,
                    'position_type' => $position->type,
                    'work_unit_id' => $employee->work_unit_id,
                    'work_unit_name' => $transfer->to_work_unit_name ?? $transfer->from_work_unit_name,
                    'start_date' => $transfer->effective_date->toDateString(),
                    'decree_number' => $transfer->decree_number,
                    'notes' => 'Dicatat otomatis dari mutasi.',
                    'is_current' => true,
                ]);

                $employee->position_id = $position->id;
            } elseif ($transfer->to_work_unit_id !== null) {
                // Pindah unit saja: entri jabatan berlaku ikut menunjuk unit baru.
                EmployeePosition::query()
                    ->where('employee_id', $employee->id)
                    ->where('is_current', true)
                    ->update(['work_unit_id' => $transfer->to_work_unit_id, 'work_unit_name' => $transfer->to_work_unit_name]);
            }

            $this->employees->syncLegacyColumns($employee);
            $employee->save();

            $transfer->update(['status' => TransferStatus::Applied, 'applied_at' => now()]);
        });
    }

    public function cancel(EmployeeTransfer $transfer): EmployeeTransfer
    {
        if ($transfer->status !== TransferStatus::Scheduled) {
            throw new ConflictException('Hanya mutasi yang belum berlaku yang dapat dibatalkan.');
        }

        $transfer->update(['status' => TransferStatus::Cancelled]);

        return $transfer;
    }

    /**
     * Terapkan mutasi terjadwal yang tanggal efektifnya sudah tiba.
     */
    public function applyDue(): int
    {
        $applied = 0;

        EmployeeTransfer::query()
            ->where('status', TransferStatus::Scheduled)
            ->whereDate('effective_date', '<=', CarbonImmutable::today())
            ->orderBy('effective_date')
            ->each(function (EmployeeTransfer $transfer) use (&$applied): void {
                $this->apply($transfer);
                $applied++;
            });

        return $applied;
    }
}
