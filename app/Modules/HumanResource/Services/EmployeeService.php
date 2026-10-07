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
use Modules\Academic\Services\LecturerAccountService;
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
 *
 * Akun login dosen (lecturers.user_id = employees.user_id, role `lecturer`)
 * dikelola LecturerAccountService — dipakai di sini supaya dosen yang
 * ditambah dari Modul SDM langsung bisa login, sama seperti dari menu Dosen.
 */
class EmployeeService
{
    /** Field dosen yang disimpan di tabel `lecturers`, bukan `employees`. */
    public const LECTURER_FIELDS = ['nidn', 'nidk', 'serdos_number', 'expertise', 'lecturer_status', 'teaching_started_at'];

    public function __construct(
        private readonly EmployeeHistoryService $history,
        private readonly LecturerAccountService $accounts,
    ) {}

    /**
     * Dosen baru otomatis dibuatkan akun login kecuali `create_account`
     * bernilai false; `user_id` menautkan akun yang sudah ada alih-alih
     * membuat akun baru. `account.password` hanya terisi kalau akun baru
     * dibuat — dikembalikan sekali ini saja (selanjutnya hanya bisa direset).
     *
     * @param  array<string, mixed>  $data
     * @return array{employee: Employee, account: array{user: User, password: string|null, created: bool}|null}
     */
    public function create(array $data, User $actor): array
    {
        $type = EmployeeType::from((string) $data['employee_type']);
        $this->assertReferences($data);

        $createAccount = (bool) ($data['create_account'] ?? true);
        $password = $data['password'] ?? null;
        $data = Arr::except($data, ['create_account', 'password']);

        return DB::transaction(function () use ($data, $type, $actor, $createAccount, $password): array {
            $employee = new Employee(Arr::except($data, [...self::LECTURER_FIELDS, 'academic_rank']));
            $employee->employee_type = $type;
            $this->syncLegacyColumns($employee);
            $employee->save();

            $account = null;

            if ($type === EmployeeType::Lecturer) {
                $lecturer = $this->upsertLecturer($employee, $data);

                if (! empty($data['academic_rank'])) {
                    $this->history->addAcademicRank($employee, [
                        'academic_rank' => $data['academic_rank'],
                        'start_date' => $data['teaching_started_at'] ?? $data['joined_at'] ?? CarbonImmutable::today()->toDateString(),
                    ], $actor);
                }

                if ($employee->user_id !== null || $createAccount) {
                    $account = $this->accounts->provision($lecturer, $password, $actor, $employee->user);
                }
            }

            // Jabatan awal langsung dicatat sebagai riwayat, supaya riwayat
            // jabatan selalu punya titik awal.
            if ($employee->position_id !== null) {
                $this->history->recordInitialPosition($employee);
            }

            return ['employee' => $employee->refresh(), 'account' => $account];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        $this->assertReferences($data, $employee);

        return DB::transaction(function () use ($employee, $data): Employee {
            $except = [...self::LECTURER_FIELDS, 'academic_rank', 'employee_type'];

            // Tautan akun dosen yang sudah ada tidak boleh diputus/diganti
            // diam-diam lewat form ubah — role `lecturer` & akunnya dikelola
            // LecturerAccountService (reset password, aktif/nonaktif).
            if ($employee->isLecturer() && $employee->lecturer?->user_id !== null) {
                $except[] = 'user_id';
            }

            $employee->fill(Arr::except($data, $except));
            $this->syncLegacyColumns($employee);
            $employee->save();

            if ($employee->isLecturer()) {
                $lecturer = $this->upsertLecturer($employee, $data);

                // Dosen tanpa akun yang baru ditautkan ke akun yang ada.
                if ($lecturer->user_id === null && $employee->user_id !== null && $employee->wasChanged('user_id')) {
                    $this->accounts->provision($lecturer, existing: $employee->user);
                }
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

            $this->syncLecturerActive($employee, false);
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
            $this->syncLecturerActive($employee, true);
        });

        return $employee->refresh();
    }

    /**
     * Soft delete — riwayat, dokumen, dan audit log pegawai tetap ada.
     * Baris `lecturers` tidak dihapus (masih dirujuk skripsi/magang), hanya
     * dinonaktifkan — begitu juga akun login dosennya.
     */
    public function delete(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $this->syncLecturerActive($employee, false);
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
    private function upsertLecturer(Employee $employee, array $data): Lecturer
    {
        $attributes = [
            ...Arr::only($data, self::LECTURER_FIELDS),
            'university_id' => $employee->university_id,
            'name' => $employee->name,
            'email' => $employee->email,
            'faculty_id' => $employee->faculty_id,
            'study_program_id' => $employee->study_program_id,
            'is_active' => $employee->is_active,
        ];

        $lecturer = $employee->lecturer;

        if ($lecturer === null) {
            $lecturer = new Lecturer(['employee_id' => $employee->id]);
            $lecturer->fill($attributes)->save();

            return $lecturer;
        }

        // Lewat LecturerAccountService supaya nama/email/status aktif akun
        // login dosen ikut berubah (dan email yang bentrok ditolak).
        return $this->accounts->updateLecturer($lecturer, $attributes);
    }

    /**
     * Pegawai dosen dinonaktifkan/diaktifkan/dihapus → status dosen dan
     * akun loginnya ikut, supaya dosen nonaktif tidak bisa login lagi.
     */
    private function syncLecturerActive(Employee $employee, bool $active): void
    {
        if ($employee->lecturer !== null) {
            $this->accounts->updateLecturer($employee->lecturer, ['is_active' => $active]);
        }
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

        if (! empty($data['supervisor_employee_id'])) {
            $supervisor = Employee::query()->whereKey($data['supervisor_employee_id'])->firstOrFail();

            // Rantai atasan tidak boleh melingkar (A atasan B, B atasan A) —
            // approver `direct_supervisor` akan berputar tanpa ujung.
            for ($cursor = $supervisor, $depth = 0; $cursor !== null && $depth < 50; $depth++) {
                if ($current !== null && $cursor->id === $current->id) {
                    throw ValidationException::withMessages(['supervisor_employee_id' => 'Atasan ini adalah bawahan (langsung/tidak langsung) dari pegawai tersebut.']);
                }
                $cursor = $cursor->supervisor_employee_id !== null
                    ? Employee::query()->whereKey($cursor->supervisor_employee_id)->first()
                    : null;
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
