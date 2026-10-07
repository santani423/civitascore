<?php

namespace Modules\HumanResource\Services;

use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Lecturer;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Enums\HasLabel;
use Modules\HumanResource\Enums\LeaveStatus;
use Modules\HumanResource\Models\LeaveRequest;

/**
 * Statistik Dashboard SDM — seluruhnya agregasi langsung dari tabel
 * (ter-scope tenant). Pegawai yang dihapus (soft delete) tidak dihitung.
 */
class HrDashboardService
{
    public function __construct(private readonly HrAlertService $alerts) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $byType = $this->countBy('employee_type');

        return [
            'stats' => [
                'total_employees' => Employee::query()->count(),
                'total_lecturers' => (int) ($byType[EmployeeType::Lecturer->value] ?? 0),
                'total_staff' => (int) ($byType[EmployeeType::Staff->value] ?? 0),
                'active_employees' => Employee::query()->where('is_active', true)->count(),
                'inactive_employees' => Employee::query()->where('is_active', false)->count(),
                'contract_employees' => Employee::query()->where('employment_status', EmploymentStatus::Contract)->count(),
                'contracts_expiring' => $this->alerts->contractsExpiring(0)['count'],
                'pending_leave_requests' => LeaveRequest::query()->where('status', LeaveStatus::Submitted)->count(),
            ],
            'charts' => [
                'by_type' => $this->labelled($byType, EmployeeType::cases()),
                'by_employment_status' => $this->labelled($this->countBy('employment_status'), EmploymentStatus::cases()),
                'by_education' => $this->labelled($this->countBy('highest_education'), EducationLevel::cases(), includeUnknown: true),
                'by_academic_rank' => $this->labelled($this->academicRankCounts(), AcademicRank::cases(), includeUnknown: true),
                'by_work_unit' => $this->workUnitCounts(),
            ],
            'alerts' => $this->alerts->all(5),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function countBy(string $column): array
    {
        // Ekspresi SQL dipilih dari daftar literal tetap, bukan interpolasi
        // nama kolom — tidak ada jalur input ke selectRaw.
        $expression = match ($column) {
            'employee_type' => 'employee_type as bucket, count(*) as total',
            'employment_status' => 'employment_status as bucket, count(*) as total',
            'highest_education' => 'highest_education as bucket, count(*) as total',
            default => throw new \InvalidArgumentException("Kolom tidak didukung: {$column}"),
        };

        return Employee::query()
            ->selectRaw($expression)
            ->groupBy($column)
            ->pluck('total', 'bucket')
            ->map(fn ($total): int => (int) $total)
            ->mapWithKeys(fn (int $total, $bucket): array => [(string) $bucket => $total])
            ->all();
    }

    /**
     * Hanya dosen yang data pegawainya masih ada (tidak dihapus).
     *
     * @return array<string, int>
     */
    private function academicRankCounts(): array
    {
        return Lecturer::query()
            ->whereIn('employee_id', Employee::query()->select('id'))
            ->selectRaw('academic_rank as bucket, count(*) as total')
            ->groupBy('academic_rank')
            ->pluck('total', 'bucket')
            ->mapWithKeys(fn ($total, $bucket): array => [(string) $bucket => (int) $total])
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, total: int}>
     */
    private function workUnitCounts(): array
    {
        return Employee::query()
            ->selectRaw('unit_kerja as bucket, count(*) as total')
            ->groupBy('unit_kerja')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn (Employee $row): array => [
                'key' => (string) $row->getAttribute('bucket'),
                'label' => (string) $row->getAttribute('bucket'),
                'total' => (int) $row->getAttribute('total'),
            ])
            ->all();
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<int, HasLabel&\BackedEnum>  $cases
     * @return array<int, array{key: string, label: string, total: int}>
     */
    private function labelled(array $counts, array $cases, bool $includeUnknown = false): array
    {
        $rows = [];

        foreach ($cases as $case) {
            $total = $counts[(string) $case->value] ?? 0;

            if ($total > 0) {
                $rows[] = ['key' => (string) $case->value, 'label' => $case->label(), 'total' => $total];
            }
        }

        if ($includeUnknown && ($counts[''] ?? 0) > 0) {
            $rows[] = ['key' => 'unknown', 'label' => 'Belum diisi', 'total' => $counts['']];
        }

        return $rows;
    }
}
