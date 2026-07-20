<?php

namespace Modules\Report\Services;

use InvalidArgumentException;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Finance\Models\Invoice;

/**
 * Cross-cutting reporting layer over data that already exists in other
 * modules — deliberately not a persisted business entity (there's nothing
 * to store; a "report" is just a named slice of Student/Grade/Invoice/
 * Lecturer+Employee re-shaped for tabular display and CSV export).
 */
class ReportService
{
    /** @var array<string, string> */
    public const REPORT_TYPES = [
        'mahasiswa' => 'Data Mahasiswa',
        'akademik' => 'Nilai Akademik',
        'keuangan' => 'Tagihan dan Pembayaran',
        'sdm' => 'Dosen dan Pegawai',
    ];

    /**
     * @return array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function build(string $type): array
    {
        return match ($type) {
            'mahasiswa' => $this->buildMahasiswa(),
            'akademik' => $this->buildAkademik(),
            'keuangan' => $this->buildKeuangan(),
            'sdm' => $this->buildSdm(),
            default => throw new InvalidArgumentException("Jenis laporan tidak dikenal: {$type}"),
        };
    }

    /**
     * @return array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    private function buildMahasiswa(): array
    {
        $students = Student::query()->with('studyProgram')->get();

        $rows = $students->map(fn (Student $student) => [
            'nim' => $student->nim,
            'nama' => $student->name,
            'program_studi' => $student->studyProgram?->name ?? '-',
            'angkatan' => $student->admission_year,
            'status' => $student->status->value,
        ])->all();

        return [
            'columns' => [
                ['key' => 'nim', 'label' => 'NIM'],
                ['key' => 'nama', 'label' => 'Nama'],
                ['key' => 'program_studi', 'label' => 'Program Studi'],
                ['key' => 'angkatan', 'label' => 'Angkatan'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'rows' => $rows,
            'summary' => [
                'total' => $students->count(),
                'aktif' => $students->where('status', StudentStatus::Active)->count(),
                'lulus' => $students->where('status', StudentStatus::Graduated)->count(),
            ],
        ];
    }

    /**
     * @return array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    private function buildAkademik(): array
    {
        $grades = Grade::query()->with(['krsItem.student', 'krsItem.classSection.course', 'krsItem.academicTerm'])->get();

        $rows = $grades->map(fn (Grade $grade) => [
            'mahasiswa' => $grade->krsItem?->student?->name ?? '-',
            'nim' => $grade->krsItem?->student?->nim ?? '-',
            'mata_kuliah' => $grade->krsItem?->classSection?->course?->name ?? '-',
            'periode' => $grade->krsItem?->academicTerm?->label() ?? '-',
            'nilai_huruf' => $grade->letter_grade?->value ?? '-',
            'skor' => $grade->score ?? '-',
        ])->all();

        $scored = $grades->pluck('score')->filter();

        return [
            'columns' => [
                ['key' => 'mahasiswa', 'label' => 'Mahasiswa'],
                ['key' => 'nim', 'label' => 'NIM'],
                ['key' => 'mata_kuliah', 'label' => 'Mata Kuliah'],
                ['key' => 'periode', 'label' => 'Periode'],
                ['key' => 'nilai_huruf', 'label' => 'Nilai Huruf'],
                ['key' => 'skor', 'label' => 'Skor'],
            ],
            'rows' => $rows,
            'summary' => [
                'total' => $grades->count(),
                'rata_rata_skor' => $scored->isNotEmpty() ? round((float) $scored->avg(), 2) : null,
            ],
        ];
    }

    /**
     * @return array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    private function buildKeuangan(): array
    {
        $invoices = Invoice::query()->with('student')->get();

        $rows = $invoices->map(fn (Invoice $invoice) => [
            'mahasiswa' => $invoice->student?->name ?? '-',
            'nim' => $invoice->student?->nim ?? '-',
            'periode' => $invoice->period,
            'jumlah' => $invoice->amount,
            'terbayar' => $invoice->paid_amount,
            'status' => $invoice->status->value,
        ])->all();

        return [
            'columns' => [
                ['key' => 'mahasiswa', 'label' => 'Mahasiswa'],
                ['key' => 'nim', 'label' => 'NIM'],
                ['key' => 'periode', 'label' => 'Periode'],
                ['key' => 'jumlah', 'label' => 'Jumlah Tagihan'],
                ['key' => 'terbayar', 'label' => 'Terbayar'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'rows' => $rows,
            'summary' => [
                'total' => $invoices->count(),
                'total_tagihan' => (float) $invoices->sum('amount'),
                'total_terbayar' => (float) $invoices->sum('paid_amount'),
            ],
        ];
    }

    /**
     * @return array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    private function buildSdm(): array
    {
        $lecturers = Lecturer::query()->with('faculty')->get()->map(fn (Lecturer $lecturer) => [
            'nama' => $lecturer->name,
            'jenis' => 'Dosen',
            'unit' => $lecturer->faculty?->name ?? '-',
            'status' => $lecturer->is_active ? 'Aktif' : 'Nonaktif',
        ]);

        $employees = Employee::query()->get()->map(fn (Employee $employee) => [
            'nama' => $employee->name,
            'jenis' => 'Pegawai',
            'unit' => $employee->unit_kerja,
            'status' => $employee->is_active ? 'Aktif' : 'Nonaktif',
        ]);

        $rows = $lecturers->concat($employees)->values();

        return [
            'columns' => [
                ['key' => 'nama', 'label' => 'Nama'],
                ['key' => 'jenis', 'label' => 'Jenis'],
                ['key' => 'unit', 'label' => 'Unit/Fakultas'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'rows' => $rows->all(),
            'summary' => [
                'total' => $rows->count(),
                'dosen' => $lecturers->count(),
                'pegawai' => $employees->count(),
            ],
        ];
    }
}
