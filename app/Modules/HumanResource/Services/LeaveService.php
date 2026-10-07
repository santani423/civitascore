<?php

namespace Modules\HumanResource\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\HrRequestType;
use Modules\HumanResource\Enums\LeaveStatus;
use Modules\HumanResource\Enums\LeaveType;
use Modules\HumanResource\Models\LeaveRequest;
use Modules\HumanResource\Services\Concerns\SavesRecordWithFiles;
use Modules\SystemSetting\Services\SystemSettingService;

class LeaveService
{
    use SavesRecordWithFiles;

    private const DEFAULT_ANNUAL_QUOTA = 12;

    public function __construct(
        private readonly HrRequestService $requests,
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data  leave_type, start_date, end_date, reason, attachment_file_id
     */
    public function create(Employee $employee, array $data, User $actor, bool $submit): LeaveRequest
    {
        $type = LeaveType::from((string) $data['leave_type']);
        $days = $this->countDays($type, $data['start_date'], $data['end_date']);

        return DB::transaction(function () use ($employee, $data, $actor, $submit, $days): LeaveRequest {
            $leave = new LeaveRequest([
                'university_id' => $employee->university_id,
                'employee_id' => $employee->id,
                'status' => LeaveStatus::Draft,
                'requested_by' => $actor->id,
                'days' => $days,
            ]);

            $this->saveWithFiles($leave, $data, ['attachment_file_id'], $actor);

            if ($submit) {
                $this->submit($leave, $actor);
            }

            return $leave->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(LeaveRequest $leave, array $data, User $actor): LeaveRequest
    {
        if ($leave->status !== LeaveStatus::Draft) {
            throw new ConflictException('Hanya pengajuan cuti berstatus draft yang dapat diubah.');
        }

        $type = LeaveType::from((string) $data['leave_type']);
        $data['days'] = $this->countDays($type, $data['start_date'], $data['end_date']);

        return $this->saveWithFiles($leave, $data, ['attachment_file_id'], $actor);
    }

    public function submit(LeaveRequest $leave, User $actor): LeaveRequest
    {
        if ($leave->status !== LeaveStatus::Draft) {
            throw new ConflictException('Pengajuan cuti ini sudah diajukan.');
        }

        $employee = $leave->employee;

        if ($employee === null) {
            throw new ConflictException('Data pegawai tidak ditemukan.');
        }

        $this->assertNoOverlap($leave);
        $this->assertWithinQuota($employee, $leave);

        return DB::transaction(function () use ($leave, $employee, $actor): LeaveRequest {
            $leave->update(['status' => LeaveStatus::Submitted, 'submitted_at' => now()]);

            $this->requests->submit($employee, HrRequestType::Leave, [
                'title' => sprintf(
                    '%s %d hari (%s s.d. %s)',
                    $leave->leave_type->label(),
                    $leave->days,
                    $leave->start_date->format('d/m/Y'),
                    $leave->end_date->format('d/m/Y'),
                ),
                'description' => $leave->reason,
                'leave_request_id' => $leave->id,
            ], $actor);

            return $leave->refresh();
        });
    }

    public function cancel(LeaveRequest $leave): LeaveRequest
    {
        if ($leave->status === LeaveStatus::Draft) {
            $leave->update(['status' => LeaveStatus::Cancelled, 'cancelled_at' => now()]);

            return $leave;
        }

        if ($leave->status !== LeaveStatus::Submitted || $leave->hrRequest === null) {
            throw new ConflictException('Hanya pengajuan cuti draft atau yang masih menunggu persetujuan yang dapat dibatalkan.');
        }

        $this->requests->cancel($leave->hrRequest);

        return $leave->refresh();
    }

    /**
     * Cuti melahirkan dihitung hari kalender; jenis lain hanya hari kerja
     * (Senin–Jumat).
     */
    public function countDays(LeaveType $type, string $start, string $end): int
    {
        $startDate = CarbonImmutable::parse($start);
        $endDate = CarbonImmutable::parse($end);

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages(['end_date' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);
        }

        if ($type === LeaveType::Maternity) {
            $days = (int) $startDate->diffInDays($endDate) + 1;
        } else {
            $days = 0;

            foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
                if (! $date->isWeekend()) {
                    $days++;
                }
            }
        }

        if ($days === 0) {
            throw ValidationException::withMessages(['start_date' => 'Rentang tanggal tidak mencakup hari kerja.']);
        }

        return $days;
    }

    /**
     * @return array{year: int, quota: int, used: int, remaining: int}
     */
    public function annualQuota(Employee $employee, ?int $year = null): array
    {
        $year ??= CarbonImmutable::today()->year;
        $quota = (int) $this->settings->get('hr.annual_leave_quota', self::DEFAULT_ANNUAL_QUOTA);

        $used = (int) LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type', LeaveType::Annual)
            ->whereIn('status', [LeaveStatus::Submitted, LeaveStatus::Approved])
            ->whereYear('start_date', $year)
            ->sum('days');

        return ['year' => $year, 'quota' => $quota, 'used' => $used, 'remaining' => max($quota - $used, 0)];
    }

    private function assertWithinQuota(Employee $employee, LeaveRequest $leave): void
    {
        if ($leave->leave_type !== LeaveType::Annual) {
            return;
        }

        $quota = $this->annualQuota($employee, $leave->start_date->year);

        if ($leave->days > $quota['remaining']) {
            throw new ConflictException("Sisa cuti tahunan {$quota['remaining']} hari, pengajuan ini {$leave->days} hari.");
        }
    }

    private function assertNoOverlap(LeaveRequest $leave): void
    {
        $overlaps = LeaveRequest::query()
            ->where('employee_id', $leave->employee_id)
            ->whereKeyNot($leave->id)
            ->whereIn('status', [LeaveStatus::Submitted, LeaveStatus::Approved])
            ->whereDate('start_date', '<=', $leave->end_date)
            ->whereDate('end_date', '>=', $leave->start_date)
            ->exists();

        if ($overlaps) {
            throw new ConflictException('Tanggal cuti bertumpang tindih dengan pengajuan cuti lain yang sedang diproses atau sudah disetujui.');
        }
    }
}
