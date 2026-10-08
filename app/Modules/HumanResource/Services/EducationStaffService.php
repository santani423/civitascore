<?php

namespace Modules\HumanResource\Services;

use Illuminate\Support\Collection;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Enums\StaffCategory;
use Modules\HumanResource\Models\WorkUnit;

/**
 * Rekap Data Tenaga Kependidikan (RANCANGAN-AKUN-SDM §5.4): komposisi
 * tendik per kategori, unit kerja, dan status kepegawaian, serta rasio
 * tendik terhadap mahasiswa/dosen per fakultas (bahan laporan akreditasi).
 *
 * Komposisi dan rasio hanya menghitung pegawai/mahasiswa aktif. Seluruh
 * query ter-scope tenant lewat TenantScoped. Pengelompokan dilakukan di
 * PHP atas kolom seperlunya, supaya tidak perlu join antar tabel tenant.
 */
class EducationStaffService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $total = Employee::query()->educationStaff()->count();

        /** @var Collection<int, Employee> $active */
        $active = Employee::query()->educationStaff()
            ->where('is_active', true)
            ->get(['id', 'staff_category', 'employment_status', 'work_unit_id', 'faculty_id']);

        return [
            'totals' => [
                'total' => $total,
                'active' => $active->count(),
                'inactive' => $total - $active->count(),
                'uncategorized' => $active->whereNull('staff_category')->count(),
            ],
            'by_category' => $this->byCategory($active),
            'by_employment_status' => $this->byEmploymentStatus($active),
            'by_work_unit' => $this->byWorkUnit($active),
            'ratios' => $this->ratios($active),
        ];
    }

    /**
     * Semua kategori ikut tampil (termasuk yang nol), supaya tabel rekap
     * selalu lengkap dan urutannya stabil.
     *
     * @param  Collection<int, Employee>  $staff
     * @return array<int, array{value: string, label: string, total: int}>
     */
    private function byCategory(Collection $staff): array
    {
        $counts = $staff->countBy(fn (Employee $employee): string => $employee->staff_category->value ?? '');

        return array_map(fn (StaffCategory $category): array => [
            'value' => $category->value,
            'label' => $category->label(),
            'total' => (int) $counts->get($category->value, 0),
        ], StaffCategory::cases());
    }

    /**
     * @param  Collection<int, Employee>  $staff
     * @return array<int, array{value: string, label: string, total: int}>
     */
    private function byEmploymentStatus(Collection $staff): array
    {
        $counts = $staff->countBy(fn (Employee $employee): string => $employee->employment_status->value);

        return array_map(fn (EmploymentStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
            'total' => (int) $counts->get($status->value, 0),
        ], EmploymentStatus::cases());
    }

    /**
     * @param  Collection<int, Employee>  $staff
     * @return array<int, array{work_unit_id: string|null, name: string, total: int, categories: array<string, int>}>
     */
    private function byWorkUnit(Collection $staff): array
    {
        $names = WorkUnit::query()
            ->whereIn('id', $staff->pluck('work_unit_id')->filter()->unique()->values())
            ->pluck('name', 'id');

        $rows = [];

        foreach ($staff as $employee) {
            $key = $employee->work_unit_id ?? '';
            $category = $employee->staff_category->value ?? 'uncategorized';

            $rows[$key] ??= [
                'work_unit_id' => $employee->work_unit_id,
                'name' => $employee->work_unit_id !== null ? (string) ($names[$employee->work_unit_id] ?? '-') : 'Belum ditempatkan',
                'total' => 0,
                'categories' => [],
            ];
            $rows[$key]['total']++;
            $rows[$key]['categories'][$category] = ($rows[$key]['categories'][$category] ?? 0) + 1;
        }

        usort($rows, fn (array $a, array $b): int => [$b['total'], $a['name']] <=> [$a['total'], $b['name']]);

        return $rows;
    }

    /**
     * Tendik dihitung ke fakultas lewat `employees.faculty_id`, atau fakultas
     * unit kerjanya bila tidak diisi. Tendik di unit non-fakultas (Rektorat,
     * Biro, Perpustakaan pusat) hanya masuk ke rasio tingkat universitas.
     *
     * @param  Collection<int, Employee>  $staff
     * @return array<string, mixed>
     */
    private function ratios(Collection $staff): array
    {
        $unitFaculty = WorkUnit::query()->whereNotNull('faculty_id')->pluck('faculty_id', 'id');
        $staffByFaculty = $staff
            ->map(fn (Employee $employee): ?string => $employee->faculty_id ?? ($unitFaculty[$employee->work_unit_id] ?? null))
            ->filter()
            ->countBy();

        $lecturersByFaculty = Employee::query()
            ->where('employee_type', EmployeeType::Lecturer)
            ->where('is_active', true)
            ->whereNotNull('faculty_id')
            ->selectRaw('faculty_id, count(*) as total')
            ->groupBy('faculty_id')
            ->pluck('total', 'faculty_id');

        $programFaculty = StudyProgram::query()->pluck('faculty_id', 'id');
        $studentsByFaculty = Student::query()
            ->where('status', StudentStatus::Active)
            ->selectRaw('study_program_id, count(*) as total')
            ->groupBy('study_program_id')
            ->pluck('total', 'study_program_id')
            ->reduce(function (array $carry, int|string $count, string $programId) use ($programFaculty): array {
                $facultyId = $programFaculty[$programId] ?? null;
                if ($facultyId !== null) {
                    $carry[$facultyId] = ($carry[$facultyId] ?? 0) + (int) $count;
                }

                return $carry;
            }, []);

        $faculties = Faculty::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Faculty $faculty): array => $this->ratioRow(
                [
                    'faculty_id' => $faculty->id,
                    'faculty_name' => $faculty->name,
                ],
                (int) $staffByFaculty->get($faculty->id, 0),
                (int) $lecturersByFaculty->get($faculty->id, 0),
                (int) ($studentsByFaculty[$faculty->id] ?? 0),
            ))
            ->values()
            ->all();

        $overall = $this->ratioRow(
            [],
            $staff->count(),
            Employee::query()->where('employee_type', EmployeeType::Lecturer)->where('is_active', true)->count(),
            Student::query()->where('status', StudentStatus::Active)->count(),
        );

        return [
            'overall' => [...$overall, 'staff_outside_faculty' => $staff->count() - $staffByFaculty->sum()],
            'by_faculty' => $faculties,
        ];
    }

    /**
     * @param  array<string, mixed>  $identity
     * @return array<string, mixed>
     */
    private function ratioRow(array $identity, int $staff, int $lecturers, int $students): array
    {
        return [
            ...$identity,
            'staff' => $staff,
            'lecturers' => $lecturers,
            'students' => $students,
            'students_per_staff' => $staff > 0 ? round($students / $staff, 1) : null,
            'lecturers_per_staff' => $staff > 0 ? round($lecturers / $staff, 1) : null,
        ];
    }
}
