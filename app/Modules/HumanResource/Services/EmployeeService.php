<?php

namespace Modules\HumanResource\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\StudyProgram;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Models\Position;
use Modules\HumanResource\Models\WorkUnit;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Models\UserUniversity;

/**
 * Siklus hidup master pegawai. Untuk dosen, baris `lecturers` (dipakai
 * Modul Akademik) ikut dibuat/diperbarui di transaksi yang sama, jadi data
 * dosen tidak pernah terduplikasi atau tidak sinkron.
 */
class EmployeeService
{
    /** Field dosen yang disimpan di tabel `lecturers`, bukan `employees`. */
    public const LECTURER_FIELDS = ['nidn', 'nidk', 'serdos_number', 'expertise', 'lecturer_status', 'teaching_started_at'];

    public function __construct(private readonly EmployeeHistoryService $history) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Employee
    {
        $type = EmployeeType::from((string) $data['employee_type']);
        $this->assertReferences($data);

        return DB::transaction(function () use ($data, $type, $actor): Employee {
            $employee = new Employee(Arr::except($data, [...self::LECTURER_FIELDS, 'academic_rank']));
            $employee->employee_type = $type;
            $this->syncLegacyColumns($employee);
            $employee->save();

            if ($type === EmployeeType::Lecturer) {
                $this->upsertLecturer($employee, $data);

                if (! empty($data['academic_rank'])) {
                    $this->history->addAcademicRank($employee, [
                        'academic_rank' => $data['academic_rank'],
                        'start_date' => $data['teaching_started_at'] ?? $data['joined_at'] ?? CarbonImmutable::today()->toDateString(),
                    ], $actor);
                }
            }

            // Jabatan awal langsung dicatat sebagai riwayat, supaya riwayat
            // jabatan selalu punya titik awal.
            if ($employee->position_id !== null) {
                $this->history->recordInitialPosition($employee);
            }

            return $employee->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        $this->assertReferences($data, $employee);

        return DB::transaction(function () use ($employee, $data): Employee {
            $employee->fill(Arr::except($data, [...self::LECTURER_FIELDS, 'academic_rank', 'employee_type']));
            $this->syncLegacyColumns($employee);
            $employee->save();

            if ($employee->isLecturer()) {
                $this->upsertLecturer($employee, $data);
            }

            return $employee->refresh();
        });
    }

    /**
     * Pastikan dosen punya baris pegawai (dosen yang dibuat dari Modul
     * Akademik, atau di-insert massal oleh seeder akademik). Idempotent.
     * university_id diisi eksplisit — aman dipanggil dari seeder yang
     * mematikan model event.
     */
    public function ensureEmployeeForLecturer(Lecturer $lecturer): Employee
    {
        if ($lecturer->employee_id !== null) {
            $existing = Employee::query()->withTrashed()->whereKey($lecturer->employee_id)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $employee = new Employee([
            'university_id' => $lecturer->university_id,
            'employee_type' => EmployeeType::Lecturer,
            'name' => $lecturer->name,
            'email' => $lecturer->email,
            'faculty_id' => $lecturer->faculty_id,
            'study_program_id' => $lecturer->study_program_id,
            'employment_status' => EmploymentStatus::Permanent,
            'is_active' => $lecturer->is_active,
        ]);
        $this->syncLegacyColumns($employee);
        $employee->save();

        $lecturer->employee_id = $employee->id;
        $lecturer->saveQuietly();

        return $employee;
    }

    public function deactivate(Employee $employee, string $reason, ?string $date): Employee
    {
        if (! $employee->is_active) {
            throw new ConflictException('Pegawai sudah berstatus nonaktif.');
        }

        DB::transaction(function () use ($employee, $reason, $date): void {
            $employee->update([
                'is_active' => false,
                'inactive_reason' => $reason,
                'inactive_at' => $date ?? CarbonImmutable::today()->toDateString(),
            ]);

            $employee->lecturer?->update(['is_active' => false]);
        });

        return $employee->refresh();
    }

    public function activate(Employee $employee): Employee
    {
        if ($employee->is_active) {
            throw new ConflictException('Pegawai sudah berstatus aktif.');
        }

        DB::transaction(function () use ($employee): void {
            $employee->update(['is_active' => true, 'inactive_reason' => null, 'inactive_at' => null]);
            $employee->lecturer?->update(['is_active' => true]);
        });

        return $employee->refresh();
    }

    /**
     * Soft delete — riwayat, dokumen, dan audit log pegawai tetap ada.
     * Baris `lecturers` tidak dihapus (masih dirujuk skripsi/magang), hanya
     * dinonaktifkan.
     */
    public function delete(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $employee->lecturer?->update(['is_active' => false]);
            $employee->delete();
        });
    }

    /**
     * Kolom teks lama `unit_kerja`/`position` dibaca Dashboard &
     * ReportService — dijaga sinkron dari master supaya fitur itu tetap
     * benar tanpa diubah.
     */
    public function syncLegacyColumns(Employee $employee): void
    {
        $unitName = $employee->work_unit_id !== null
            ? WorkUnit::query()->whereKey($employee->work_unit_id)->value('name')
            : null;
        $facultyName = $employee->faculty_id !== null
            ? Faculty::query()->whereKey($employee->faculty_id)->value('name')
            : null;

        $employee->unit_kerja = $unitName ?? $facultyName ?? ($employee->unit_kerja ?: '-');

        if ($employee->position_id !== null) {
            $employee->position = Position::query()->whereKey($employee->position_id)->value('name');
        } elseif ($employee->position === null && $employee->employee_type === EmployeeType::Lecturer) {
            $employee->position = 'Dosen';
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertLecturer(Employee $employee, array $data): void
    {
        $lecturer = $employee->lecturer ?? new Lecturer(['employee_id' => $employee->id]);

        $lecturer->fill(Arr::only($data, self::LECTURER_FIELDS));
        $lecturer->fill([
            'university_id' => $employee->university_id,
            'name' => $employee->name,
            'email' => $employee->email,
            'faculty_id' => $employee->faculty_id,
            'study_program_id' => $employee->study_program_id,
            'is_active' => $employee->is_active,
        ]);

        $lecturer->save();
    }

    /**
     * FK yang dikirim klien dicek lewat query ter-scope tenant — id milik
     * universitas lain dibaca sebagai "tidak ada" (404), tidak membocorkan
     * keberadaannya. Pola sama dengan LecturerController::store().
     *
     * @param  array<string, mixed>  $data
     */
    private function assertReferences(array $data, ?Employee $current = null): void
    {
        if (! empty($data['work_unit_id'])) {
            WorkUnit::query()->whereKey($data['work_unit_id'])->firstOrFail();
        }
        if (! empty($data['position_id'])) {
            Position::query()->whereKey($data['position_id'])->firstOrFail();
        }
        if (! empty($data['faculty_id'])) {
            Faculty::query()->whereKey($data['faculty_id'])->firstOrFail();
        }
        if (! empty($data['study_program_id'])) {
            $program = StudyProgram::query()->whereKey($data['study_program_id'])->firstOrFail();

            if (! empty($data['faculty_id']) && $program->faculty_id !== $data['faculty_id']) {
                throw ValidationException::withMessages(['study_program_id' => 'Program studi tidak berada di fakultas yang dipilih.']);
            }
        }

        if (! empty($data['user_id'])) {
            $isMember = UserUniversity::query()
                ->where('user_id', $data['user_id'])
                ->where('university_id', app(TenantContext::class)->universityId())
                ->where('status', MembershipStatus::Active)
                ->exists();

            if (! $isMember) {
                throw ValidationException::withMessages(['user_id' => 'Akun pengguna tidak terdaftar pada universitas ini.']);
            }

            $linkedElsewhere = Employee::query()->withTrashed()
                ->where('user_id', $data['user_id'])
                ->when($current !== null, fn ($query) => $query->whereKeyNot($current->id))
                ->exists();

            if ($linkedElsewhere) {
                throw ValidationException::withMessages(['user_id' => 'Akun pengguna ini sudah tertaut ke pegawai lain.']);
            }
        }
    }
}
