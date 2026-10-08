<?php

namespace Modules\HumanResource\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Models\EmployeeCertification;
use Modules\HumanResource\Models\EmployeeContract;
use Modules\HumanResource\Models\EmployeeTraining;
use Modules\HumanResource\Models\LeaveRequest;

/**
 * Laporan SDM. Bentuk keluaran sama dengan Modules\Report\Services\
 * ReportService ({columns, rows, summary}) supaya pola tampilan tabel &
 * export di frontend bisa dipakai ulang.
 *
 * @phpstan-type Report array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
 */
class HrReportService
{
    /** @var array<string, string> */
    public const REPORT_TYPES = [
        'employees' => 'Laporan Seluruh Pegawai',
        'lecturers' => 'Laporan Dosen',
        'staff' => 'Laporan Tenaga Kependidikan',
        'by_unit' => 'Rekap Pegawai per Unit Kerja',
        'by_education' => 'Rekap Pegawai per Pendidikan Terakhir',
        'by_employment_status' => 'Rekap Pegawai per Status Kepegawaian',
        'by_position' => 'Rekap Pegawai per Jabatan',
        'contracts' => 'Laporan Kontrak Kerja',
        'leave' => 'Laporan Cuti & Izin',
        'certifications' => 'Laporan Sertifikasi',
        'trainings' => 'Laporan Pelatihan',
    ];

    /**
     * @param  array<string, mixed>  $filters  employee_type, is_active, work_unit_id, employment_status, staff_category, search, date_from, date_to
     * @return Report
     */
    public function build(string $type, array $filters = []): array
    {
        return match ($type) {
            'employees' => $this->employees($filters),
            'lecturers' => $this->lecturers($filters),
            'staff' => $this->staff($filters),
            'by_unit' => $this->groupedByEmployeeColumn('unit_kerja', 'Unit Kerja', $filters),
            'by_education' => $this->groupedByEmployeeColumn('highest_education', 'Pendidikan Terakhir', $filters, fn ($value) => $value !== null && $value !== '' ? EducationLevel::from((string) $value)->label() : 'Belum diisi'),
            'by_employment_status' => $this->groupedByEmployeeColumn('employment_status', 'Status Kepegawaian', $filters, fn ($value) => EmploymentStatus::from((string) $value)->label()),
            'by_position' => $this->groupedByEmployeeColumn('position', 'Jabatan', $filters),
            'contracts' => $this->contracts($filters),
            'leave' => $this->leave($filters),
            'certifications' => $this->certifications($filters),
            'trainings' => $this->trainings($filters),
            default => throw new InvalidArgumentException("Jenis laporan tidak dikenal: {$type}"),
        };
    }

    /**
     * Query pegawai yang sama dengan list Data Pegawai — dipakai juga oleh
     * export list supaya hasil export = apa yang sedang difilter di layar.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Employee>
     */
    public function employeeQuery(array $filters): Builder
    {
        return Employee::query()
            ->when(! empty($filters['employee_type']), fn (Builder $query) => $query->where('employee_type', $filters['employee_type']))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn (Builder $query) => $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)))
            ->when(! empty($filters['work_unit_id']), fn (Builder $query) => $query->where('work_unit_id', $filters['work_unit_id']))
            ->when(! empty($filters['employment_status']), fn (Builder $query) => $query->where('employment_status', $filters['employment_status']))
            ->when(! empty($filters['staff_category']), fn (Builder $query) => $query->where('staff_category', $filters['staff_category']))
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $term = '%'.$filters['search'].'%';
                $query->where(fn (Builder $inner) => $inner->where('name', 'like', $term)->orWhere('nip', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderBy('name');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Report
     */
    private function employees(array $filters): array
    {
        $employees = $this->employeeQuery($filters)->get();

        return [
            'columns' => $this->columns([
                'nip' => 'NIP', 'nama' => 'Nama', 'jenis' => 'Jenis', 'unit_kerja' => 'Unit Kerja', 'jabatan' => 'Jabatan',
                'status_kepegawaian' => 'Status Kepegawaian', 'pendidikan' => 'Pendidikan Terakhir', 'tanggal_masuk' => 'Tanggal Masuk',
                'status' => 'Status',
            ]),
            'rows' => $employees->map(fn (Employee $employee): array => [
                'nip' => $employee->nip ?? '-',
                'nama' => $employee->name,
                'jenis' => $employee->employee_type->label(),
                'unit_kerja' => $employee->unit_kerja,
                'jabatan' => $employee->position ?? '-',
                'status_kepegawaian' => $employee->employment_status->label(),
                'pendidikan' => $employee->highest_education?->label() ?? '-',
                'tanggal_masuk' => $employee->joined_at?->format('d/m/Y') ?? '-',
                'status' => $employee->is_active ? 'Aktif' : 'Nonaktif',
            ])->all(),
            'summary' => [
                'total' => $employees->count(),
                'dosen' => $employees->where('employee_type', EmployeeType::Lecturer)->count(),
                'tenaga_kependidikan' => $employees->where('employee_type', EmployeeType::Staff)->count(),
                'aktif' => $employees->where('is_active', true)->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Report
     */
    private function lecturers(array $filters): array
    {
        $employees = $this->employeeQuery([...$filters, 'employee_type' => EmployeeType::Lecturer->value])
            ->with('lecturer.studyProgram', 'faculty')
            ->get();

        return [
            'columns' => $this->columns([
                'nidn' => 'NIDN', 'nidk' => 'NIDK', 'nama' => 'Nama', 'fakultas' => 'Fakultas', 'prodi' => 'Program Studi',
                'jabatan_akademik' => 'Jabatan Akademik', 'pendidikan' => 'Pendidikan', 'status_dosen' => 'Status Dosen',
                'serdos' => 'No. Serdos', 'bidang_keahlian' => 'Bidang Keahlian',
            ]),
            'rows' => $employees->map(fn (Employee $employee): array => [
                'nidn' => $employee->lecturer->nidn ?? '-',
                'nidk' => $employee->lecturer->nidk ?? '-',
                'nama' => $employee->name,
                'fakultas' => $employee->faculty->name ?? '-',
                'prodi' => $employee->lecturer?->studyProgram->name ?? '-',
                'jabatan_akademik' => $employee->lecturer?->academic_rank?->label() ?? '-',
                'pendidikan' => $employee->highest_education?->label() ?? '-',
                'status_dosen' => $employee->lecturer?->lecturer_status?->label() ?? '-',
                'serdos' => $employee->lecturer->serdos_number ?? '-',
                'bidang_keahlian' => $employee->lecturer->expertise ?? '-',
            ])->all(),
            'summary' => [
                'total' => $employees->count(),
                'bersertifikat_pendidik' => $employees->filter(fn (Employee $employee) => ! empty($employee->lecturer->serdos_number))->count(),
                'pendidikan_s3' => $employees->where('highest_education', EducationLevel::S3)->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Report
     */
    private function staff(array $filters): array
    {
        $employees = $this->employeeQuery([...$filters, 'employee_type' => EmployeeType::Staff->value])->get();

        return [
            'columns' => $this->columns([
                'nip' => 'NIP', 'nama' => 'Nama', 'kategori' => 'Kategori', 'unit_kerja' => 'Unit Kerja', 'jabatan' => 'Jabatan',
                'penugasan' => 'Penugasan Lab/Fasilitas', 'status_kepegawaian' => 'Status Kepegawaian', 'pendidikan' => 'Pendidikan',
                'status' => 'Status',
            ]),
            'rows' => $employees->map(fn (Employee $employee): array => [
                'nip' => $employee->nip ?? '-',
                'nama' => $employee->name,
                'kategori' => $employee->staff_category?->label() ?? '-',
                'unit_kerja' => $employee->unit_kerja,
                'jabatan' => $employee->position ?? '-',
                'penugasan' => $employee->assigned_facility ?? '-',
                'status_kepegawaian' => $employee->employment_status->label(),
                'pendidikan' => $employee->highest_education?->label() ?? '-',
                'status' => $employee->is_active ? 'Aktif' : 'Nonaktif',
            ])->all(),
            'summary' => [
                'total' => $employees->count(),
                'aktif' => $employees->where('is_active', true)->count(),
            ],
        ];
    }

    /**
     * Rekap jumlah pegawai per nilai satu kolom, dipecah dosen vs tendik.
     *
     * @param  array<string, mixed>  $filters
     * @param  (callable(mixed): string)|null  $labeler
     * @return Report
     */
    private function groupedByEmployeeColumn(string $column, string $label, array $filters, ?callable $labeler = null): array
    {
        $expression = match ($column) {
            'unit_kerja' => 'unit_kerja as bucket, employee_type, count(*) as total',
            'highest_education' => 'highest_education as bucket, employee_type, count(*) as total',
            'employment_status' => 'employment_status as bucket, employee_type, count(*) as total',
            'position' => 'position as bucket, employee_type, count(*) as total',
            default => throw new InvalidArgumentException("Kolom tidak didukung: {$column}"),
        };

        $rows = $this->employeeQuery($filters)
            ->reorder()
            ->selectRaw($expression)
            ->groupBy($column, 'employee_type')
            ->get()
            ->groupBy(fn ($row) => (string) $row->getAttribute('bucket'))
            ->map(function ($group, string $bucket) use ($labeler): array {
                $lecturers = (int) $group->first(fn ($row) => $row->getRawOriginal('employee_type') === EmployeeType::Lecturer->value)?->getAttribute('total');
                $staff = (int) $group->first(fn ($row) => $row->getRawOriginal('employee_type') === EmployeeType::Staff->value)?->getAttribute('total');

                return [
                    'kelompok' => $labeler !== null ? $labeler($bucket) : ($bucket !== '' ? $bucket : 'Belum diisi'),
                    'dosen' => $lecturers,
                    'tenaga_kependidikan' => $staff,
                    'total' => $lecturers + $staff,
                ];
            })
            ->sortByDesc('total')
            ->values();

        return [
            'columns' => $this->columns(['kelompok' => $label, 'dosen' => 'Dosen', 'tenaga_kependidikan' => 'Tenaga Kependidikan', 'total' => 'Total']),
            'rows' => $rows->all(),
            'summary' => ['jumlah_kelompok' => $rows->count(), 'total_pegawai' => $rows->sum('total')],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Report
     */
    private function contracts(array $filters): array
    {
        $contracts = $this->withinDates(EmployeeContract::query(), 'start_date', $filters)
            ->with('employee:id,name,nip')
            ->orderBy('end_date')
            ->get();

        return [
            'columns' => $this->columns([
                'nomor' => 'No. Kontrak', 'pegawai' => 'Pegawai', 'jenis' => 'Jenis Kontrak', 'mulai' => 'Mulai',
                'berakhir' => 'Berakhir', 'sisa_hari' => 'Sisa Hari', 'status' => 'Status',
            ]),
            'rows' => $contracts->map(fn (EmployeeContract $contract): array => [
                'nomor' => $contract->contract_number,
                'pegawai' => $contract->employee->name ?? '-',
                'jenis' => $contract->contract_type->label(),
                'mulai' => $contract->start_date->format('d/m/Y'),
                'berakhir' => $contract->end_date?->format('d/m/Y') ?? '-',
                'sisa_hari' => $contract->daysRemaining() ?? '-',
                'status' => $contract->status->label(),
            ])->all(),
            'summary' => [
                'total' => $contracts->count(),
                'aktif' => $contracts->where('status.value', 'active')->count(),
                'berakhir_90_hari' => $contracts->filter(fn (EmployeeContract $contract) => $contract->status->value === 'active'
                    && ($days = $contract->daysRemaining()) !== null && $days >= 0 && $days <= 90)->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Report
     */
    private function leave(array $filters): array
    {
        $leaves = $this->withinDates(LeaveRequest::query(), 'start_date', $filters)
            ->with('employee:id,name')
            ->orderByDesc('start_date')
            ->get();

        return [
            'columns' => $this->columns([
                'pegawai' => 'Pegawai', 'jenis' => 'Jenis', 'mulai' => 'Mulai', 'selesai' => 'Selesai', 'hari' => 'Hari', 'status' => 'Status',
            ]),
            'rows' => $leaves->map(fn (LeaveRequest $leave): array => [
                'pegawai' => $leave->employee->name ?? '-',
                'jenis' => $leave->leave_type->label(),
                'mulai' => $leave->start_date->format('d/m/Y'),
                'selesai' => $leave->end_date->format('d/m/Y'),
                'hari' => $leave->days,
                'status' => $leave->status->label(),
            ])->all(),
            'summary' => [
                'total_pengajuan' => $leaves->count(),
                'disetujui' => $leaves->where('status.value', 'approved')->count(),
                'total_hari_disetujui' => $leaves->where('status.value', 'approved')->sum('days'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Report
     */
    private function certifications(array $filters): array
    {
        $certifications = $this->withinDates(EmployeeCertification::query(), 'issued_at', $filters)
            ->with('employee:id,name')
            ->orderByDesc('issued_at')
            ->get();

        $today = CarbonImmutable::today();

        return [
            'columns' => $this->columns([
                'pegawai' => 'Pegawai', 'nama' => 'Sertifikasi', 'lembaga' => 'Lembaga', 'nomor' => 'No. Sertifikat',
                'terbit' => 'Terbit', 'kedaluwarsa' => 'Kedaluwarsa',
            ]),
            'rows' => $certifications->map(fn (EmployeeCertification $certification): array => [
                'pegawai' => $certification->employee->name ?? '-',
                'nama' => $certification->name,
                'lembaga' => $certification->issuer,
                'nomor' => $certification->certificate_number ?? '-',
                'terbit' => $certification->issued_at->format('d/m/Y'),
                'kedaluwarsa' => $certification->expires_at?->format('d/m/Y') ?? 'Tidak ada',
            ])->all(),
            'summary' => [
                'total' => $certifications->count(),
                'masih_berlaku' => $certifications->filter(fn (EmployeeCertification $certification) => $certification->expires_at === null || $certification->expires_at->gte($today))->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Report
     */
    private function trainings(array $filters): array
    {
        $trainings = $this->withinDates(EmployeeTraining::query(), 'start_date', $filters)
            ->with('employee:id,name')
            ->orderByDesc('start_date')
            ->get();

        return [
            'columns' => $this->columns([
                'pegawai' => 'Pegawai', 'pelatihan' => 'Pelatihan', 'penyelenggara' => 'Penyelenggara', 'tanggal' => 'Tanggal',
                'durasi' => 'Durasi (jam)', 'biaya' => 'Biaya', 'status' => 'Status',
            ]),
            'rows' => $trainings->map(fn (EmployeeTraining $training): array => [
                'pegawai' => $training->employee->name ?? '-',
                'pelatihan' => $training->name,
                'penyelenggara' => $training->organizer,
                'tanggal' => $training->start_date->format('d/m/Y'),
                'durasi' => $training->duration_hours ?? '-',
                'biaya' => $training->cost !== null ? (float) $training->cost : '-',
                'status' => $training->status->label(),
            ])->all(),
            'summary' => [
                'total' => $trainings->count(),
                'total_jam' => (float) $trainings->sum(fn (EmployeeTraining $training) => (float) $training->duration_hours),
                'total_biaya' => (float) $trainings->sum(fn (EmployeeTraining $training) => (float) $training->cost),
            ],
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    private function withinDates(Builder $query, string $column, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['date_from']), fn (Builder $builder) => $builder->whereDate($column, '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn (Builder $builder) => $builder->whereDate($column, '<=', $filters['date_to']));
    }

    /**
     * @param  array<string, string>  $map
     * @return array<int, array{key: string, label: string}>
     */
    private function columns(array $map): array
    {
        return array_map(fn (string $key, string $label): array => ['key' => $key, 'label' => $label], array_keys($map), $map);
    }
}
