<?php

namespace Modules\HumanResource\Database\Seeders;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\FileManagement\Models\FileUpload;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\ContractStatus;
use Modules\HumanResource\Enums\ContractType;
use Modules\HumanResource\Enums\DocumentStatus;
use Modules\HumanResource\Enums\DocumentType;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Enums\Gender;
use Modules\HumanResource\Enums\HrRequestStatus;
use Modules\HumanResource\Enums\HrRequestType;
use Modules\HumanResource\Enums\LeaveStatus;
use Modules\HumanResource\Enums\LeaveType;
use Modules\HumanResource\Enums\LecturerActivityType;
use Modules\HumanResource\Enums\LecturerStatus;
use Modules\HumanResource\Enums\PerformanceCategory;
use Modules\HumanResource\Enums\PerformanceStatus;
use Modules\HumanResource\Enums\PositionType;
use Modules\HumanResource\Enums\StaffCategory;
use Modules\HumanResource\Enums\TrainingStatus;
use Modules\HumanResource\Enums\WorkUnitType;
use Modules\HumanResource\Models\EmployeeCertification;
use Modules\HumanResource\Models\EmployeeContract;
use Modules\HumanResource\Models\EmployeeDocument;
use Modules\HumanResource\Models\EmployeeEducation;
use Modules\HumanResource\Models\EmployeePosition;
use Modules\HumanResource\Models\EmployeeRank;
use Modules\HumanResource\Models\EmployeeTraining;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Models\LeaveRequest;
use Modules\HumanResource\Models\LecturerAcademicRank;
use Modules\HumanResource\Models\LecturerActivity;
use Modules\HumanResource\Models\PerformanceReview;
use Modules\HumanResource\Models\Position;
use Modules\HumanResource\Models\Rank;
use Modules\HumanResource\Models\WorkUnit;
use Modules\HumanResource\Services\EmployeeService;
use Modules\HumanResource\Services\HrNotificationService;
use Modules\HumanResource\Services\HrRequestService;
use Modules\HumanResource\Services\LeaveService;
use Modules\Tenancy\Models\University;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/**
 * Data contoh Modul SDM untuk setiap universitas demo.
 *
 * Idempotent — aman dijalankan berulang: master pakai updateOrCreate
 * berdasarkan kode; data pegawai hanya diisi kolom yang masih kosong
 * (tidak pernah menimpa data asli); data contoh riwayat/kontrak/dokumen/
 * cuti hanya dibuat kalau pegawai/universitas itu belum punya sama sekali.
 *
 * Mengikuti konvensi DatabaseSeeder (WithoutModelEvents): university_id
 * diisi eksplisit, tidak bergantung pada event TenantScoped, dan tidak
 * membanjiri audit_logs. Satu-satunya pengecualian adalah contoh pengajuan
 * yang masih menunggu: dibuat lewat engine ApprovalWorkflow sungguhan
 * (supaya benar-benar bisa disetujui dari aplikasi), dengan model event
 * dinyalakan sementara (lihat withModelEvents()).
 *
 * Jalankan terpisah: php artisan db:seed --class="Modules\\HumanResource\\Database\\Seeders\\HumanResourceSeeder"
 */
class HumanResourceSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string, 2: WorkUnitType}> */
    private const WORK_UNITS = [
        ['REKT', 'Rektorat', WorkUnitType::Rectorate],
        ['BAAK', 'Biro Administrasi Akademik', WorkUnitType::Bureau],
        ['BKEU', 'Biro Keuangan', WorkUnitType::Bureau],
        ['BSDM', 'Biro Sumber Daya Manusia', WorkUnitType::Bureau],
        ['PERPUS', 'Perpustakaan', WorkUnitType::Library],
        ['UPTTIK', 'UPT Teknologi Informasi', WorkUnitType::Unit],
        ['LABTER', 'Laboratorium Terpadu', WorkUnitType::Laboratory],
    ];

    /** @var array<int, array{0: string, 1: string, 2: PositionType}> */
    private const POSITIONS = [
        ['REKTOR', 'Rektor', PositionType::Structural],
        ['WAREK', 'Wakil Rektor', PositionType::Structural],
        ['DEKAN', 'Dekan', PositionType::Structural],
        ['KAPRODI', 'Ketua Program Studi', PositionType::Structural],
        ['KABIRO', 'Kepala Biro', PositionType::Structural],
        ['KABAG', 'Kepala Bagian', PositionType::Structural],
        ['STAFADM', 'Staf Administrasi', PositionType::Functional],
        ['LABORAN', 'Pranata Laboratorium Pendidikan', PositionType::Functional],
        ['PUSTAKAWAN', 'Pustakawan', PositionType::Functional],
        ['PRAKOM', 'Pranata Komputer', PositionType::Functional],
        ['DOSEN', 'Dosen', PositionType::Functional],
    ];

    /** @var array<int, array{0: string, 1: string}> */
    private const RANKS = [
        ['III/a', 'Penata Muda'], ['III/b', 'Penata Muda Tingkat I'], ['III/c', 'Penata'], ['III/d', 'Penata Tingkat I'],
        ['IV/a', 'Pembina'], ['IV/b', 'Pembina Tingkat I'], ['IV/c', 'Pembina Utama Muda'], ['IV/d', 'Pembina Utama Madya'],
        ['IV/e', 'Pembina Utama'],
    ];

    private const EXPERTISE = ['Sistem Informasi', 'Rekayasa Perangkat Lunak', 'Manajemen', 'Akuntansi', 'Hukum Bisnis', 'Kesehatan Masyarakat', 'Pendidikan', 'Teknik Sipil'];

    private const BIRTH_PLACES = ['Jakarta', 'Bandung', 'Yogyakarta', 'Semarang', 'Surabaya', 'Medan', 'Makassar', 'Denpasar'];

    public function run(): void
    {
        $tenant = app(TenantContext::class);

        foreach (University::query()->orderBy('created_at')->get() as $university) {
            $tenant->setUniversityId($university->id);
            $this->seedUniversity($university);
        }

        $tenant->setUniversityId(null);
    }

    private function seedUniversity(University $university): void
    {
        $units = $this->seedWorkUnits($university);
        $positions = $this->seedPositions($university);
        $ranks = $this->seedRanks($university);

        $employees = app(EmployeeService::class);
        Lecturer::query()->whereNull('employee_id')->each(fn (Lecturer $lecturer) => $employees->ensureEmployeeForLecturer($lecturer));

        $this->enrichEmployees($university, $units, $positions);
        $hrAdmin = $this->userWithRole('hr_administrator');
        $this->linkDemoAccounts($university, $units, $positions);

        $this->seedHistories($university, $ranks);
        $this->seedContracts($university);
        $this->seedDevelopment($university);
        $this->seedPerformance($university);

        if ($hrAdmin !== null) {
            $this->seedDocuments($university, $hrAdmin);
            $this->seedLeaveAndRequests($university, $hrAdmin);
        }
    }

    // ---- Master ----------------------------------------------------------

    /**
     * @return Collection<string, WorkUnit> unit per nama
     */
    private function seedWorkUnits(University $university): Collection
    {
        foreach (self::WORK_UNITS as [$code, $name, $type]) {
            WorkUnit::query()->updateOrCreate(
                ['university_id' => $university->id, 'code' => $code],
                ['name' => $name, 'type' => $type, 'is_active' => true],
            );
        }

        $rectorate = WorkUnit::query()->where('code', 'REKT')->first();

        foreach (Faculty::query()->get() as $faculty) {
            WorkUnit::query()->updateOrCreate(
                ['university_id' => $university->id, 'code' => 'FAK-'.Str::upper($faculty->code)],
                ['name' => $faculty->name, 'type' => WorkUnitType::Faculty, 'faculty_id' => $faculty->id, 'parent_id' => $rectorate?->id, 'is_active' => true],
            );
        }

        // Unit kerja teks lama tenaga kependidikan yang belum punya master →
        // dibuatkan master. Dosen tidak ikut: unitnya adalah fakultas.
        $legacyUnits = Employee::query()
            ->where('employee_type', EmployeeType::Staff)
            ->whereNull('work_unit_id')
            ->distinct()
            ->pluck('unit_kerja');

        foreach ($legacyUnits as $unitName) {
            if ($unitName === null || $unitName === '' || $unitName === '-' || WorkUnit::query()->where('name', $unitName)->exists()) {
                continue;
            }

            WorkUnit::query()->create([
                'university_id' => $university->id,
                'code' => 'UK-'.Str::upper(substr(md5((string) $unitName), 0, 6)),
                'name' => $unitName,
                'type' => WorkUnitType::Unit,
                'parent_id' => $rectorate?->id,
                'is_active' => true,
            ]);
        }

        return WorkUnit::query()->get()->keyBy('name');
    }

    /**
     * @return Collection<string, Position> jabatan per kode
     */
    private function seedPositions(University $university): Collection
    {
        foreach (self::POSITIONS as [$code, $name, $type]) {
            Position::query()->updateOrCreate(
                ['university_id' => $university->id, 'code' => $code],
                ['name' => $name, 'type' => $type, 'is_active' => true],
            );
        }

        return Position::query()->get()->keyBy('code');
    }

    /**
     * @return Collection<int, Rank>
     */
    private function seedRanks(University $university): Collection
    {
        foreach (self::RANKS as $index => [$grade, $name]) {
            Rank::query()->updateOrCreate(
                ['university_id' => $university->id, 'grade' => $grade],
                ['name' => $name, 'level' => $index + 1, 'is_active' => true],
            );
        }

        return Rank::query()->orderBy('level')->get();
    }

    // ---- Pegawai ---------------------------------------------------------

    /**
     * Lengkapi data pegawai hasil seeder akademik. joined_at kosong dipakai
     * sebagai penanda "belum pernah dilengkapi" — pegawai yang sudah
     * diisi (oleh seeder ini atau oleh pengguna) tidak disentuh lagi.
     *
     * @param  Collection<string, WorkUnit>  $units
     * @param  Collection<string, Position>  $positions
     */
    private function enrichEmployees(University $university, Collection $units, Collection $positions): void
    {
        $prefix = Str::upper(Str::substr($university->code, 0, 4));
        $sequence = 0;

        Employee::query()->whereNull('joined_at')->orderBy('created_at')->orderBy('id')->each(
            function (Employee $employee) use ($units, $positions, $prefix, &$sequence): void {
                $sequence++;
                $isLecturer = $employee->employee_type === EmployeeType::Lecturer;
                $lecturer = $isLecturer ? $employee->lecturer : null;

                $unit = $isLecturer && $employee->faculty_id !== null
                    ? $units->first(fn (WorkUnit $workUnit) => $workUnit->faculty_id === $employee->faculty_id)
                    : $units->get($employee->unit_kerja);

                $category = $isLecturer ? null : $this->staffCategoryFor($employee->unit_kerja);
                $position = $isLecturer ? $positions->get('DOSEN') : $positions->get(match ($category) {
                    StaffCategory::Laboratory => 'LABORAN',
                    StaffCategory::Librarian => 'PUSTAKAWAN',
                    StaffCategory::InformationTechnology => 'PRAKOM',
                    default => 'STAFADM',
                });

                $nip = sprintf('%s%s%05d', $prefix, $isLecturer ? 'D' : 'T', $sequence);
                if ($employee->nip === null && ! Employee::query()->withTrashed()->where('nip', $nip)->exists()) {
                    $employee->nip = $nip;
                }

                $employee->work_unit_id ??= $unit?->id;
                $employee->position_id ??= $position?->id;
                $employee->gender ??= $sequence % 2 === 0 ? Gender::Female : Gender::Male;
                $employee->birth_place ??= self::BIRTH_PLACES[$sequence % count(self::BIRTH_PLACES)];
                $employee->birth_date ??= CarbonImmutable::create(1965 + ($sequence % 30), ($sequence % 12) + 1, ($sequence % 27) + 1);
                $employee->phone ??= sprintf('0812%08d', 10000000 + $sequence);
                $employee->joined_at = CarbonImmutable::create(2005 + ($sequence % 18), ($sequence % 12) + 1, 1);
                $employee->staff_category ??= $category;

                // Laboran/pustakawan/pranata komputer bertanggung jawab atas
                // fasilitas unitnya sendiri (lab, perpustakaan, ruang server).
                if (in_array($category, [StaffCategory::Laboratory, StaffCategory::Librarian, StaffCategory::InformationTechnology], true)) {
                    $employee->assigned_facility ??= $employee->unit_kerja;
                }

                if ($employee->employment_status === EmploymentStatus::Permanent && ! $isLecturer) {
                    $employee->employment_status = match (true) {
                        $sequence % 5 === 0 => EmploymentStatus::Contract,
                        $sequence % 9 === 0 => EmploymentStatus::Honorary,
                        default => EmploymentStatus::Permanent,
                    };
                }

                if ($unit !== null) {
                    $employee->unit_kerja = $unit->name;
                }
                if ($position !== null) {
                    $employee->position = $position->name;
                }

                $employee->saveQuietly();

                if ($lecturer !== null) {
                    $lecturer->lecturer_status ??= LecturerStatus::Permanent;
                    $lecturer->expertise ??= self::EXPERTISE[$sequence % count(self::EXPERTISE)];
                    $lecturer->teaching_started_at ??= $employee->joined_at;
                    $lecturer->serdos_number ??= $sequence % 3 === 0 ? null : sprintf('SERDOS%07d', $sequence);
                    $lecturer->saveQuietly();
                }
            },
        );
    }

    /**
     * Akun demo pegawai@ / dosen@ / dosenpa@ ditautkan ke data pegawai,
     * supaya layanan mandiri SDM (Pengajuan Saya) bisa langsung dicoba.
     *
     * @param  Collection<string, WorkUnit>  $units
     * @param  Collection<string, Position>  $positions
     */
    private function linkDemoAccounts(University $university, Collection $units, Collection $positions): void
    {
        $employeeUser = $this->userWithRole('employee');

        if ($employeeUser !== null && ! Employee::query()->withTrashed()->where('user_id', $employeeUser->id)->exists()) {
            $unit = $units->get('Biro Sumber Daya Manusia');
            $position = $positions->get('STAFADM');

            Employee::query()->create([
                'university_id' => $university->id,
                'user_id' => $employeeUser->id,
                'employee_type' => EmployeeType::Staff,
                'nip' => Str::upper(Str::substr($university->code, 0, 4)).'PEG00001',
                'name' => $employeeUser->name,
                'email' => $employeeUser->email,
                'gender' => Gender::Female,
                'birth_place' => 'Jakarta',
                'birth_date' => '1990-05-12',
                'phone' => '081200000001',
                'employment_status' => EmploymentStatus::Contract,
                'work_unit_id' => $unit?->id,
                'unit_kerja' => $unit->name ?? 'Biro Sumber Daya Manusia',
                'position_id' => $position?->id,
                'position' => $position->name ?? 'Staf Administrasi',
                'staff_category' => StaffCategory::Administration,
                'highest_education' => EducationLevel::S1,
                'joined_at' => '2022-01-03',
                'is_active' => true,
            ]);
        }

        foreach (['lecturer', 'academic_advisor'] as $roleSlug) {
            $user = $this->userWithRole($roleSlug);

            if ($user === null || Employee::query()->withTrashed()->where('user_id', $user->id)->exists()) {
                continue;
            }

            $employee = Employee::query()
                ->where('employee_type', EmployeeType::Lecturer)
                ->whereNull('user_id')
                ->orderBy('nip')
                ->first();

            if ($employee !== null) {
                $employee->user_id = $user->id;
                $employee->saveQuietly();

                continue;
            }

            // Universitas tanpa data dosen (mis. tenant yang datanya diimpor
            // terpisah) → buatkan satu dosen untuk akun demo ini.
            $employee = Employee::query()->create([
                'university_id' => $university->id,
                'user_id' => $user->id,
                'employee_type' => EmployeeType::Lecturer,
                'name' => $user->name,
                'email' => $user->email,
                'employment_status' => EmploymentStatus::Permanent,
                'unit_kerja' => 'Dosen',
                'position_id' => $positions->get('DOSEN')?->id,
                'position' => 'Dosen',
                'joined_at' => '2015-09-01',
                'is_active' => true,
            ]);

            Lecturer::query()->create([
                'university_id' => $university->id,
                'employee_id' => $employee->id,
                'nidn' => Str::upper(Str::substr($university->code, 0, 4)).'DEMO'.strtoupper(substr($roleSlug, 0, 3)),
                'name' => $user->name,
                'email' => $user->email,
                'lecturer_status' => LecturerStatus::Permanent,
                'is_active' => true,
            ]);
        }
    }

    // ---- Riwayat ---------------------------------------------------------

    /**
     * @param  Collection<int, Rank>  $ranks
     */
    private function seedHistories(University $university, Collection $ranks): void
    {
        $index = 0;

        Employee::query()->with('lecturer')->orderBy('nip')->each(function (Employee $employee) use ($university, $ranks, &$index): void {
            $index++;
            $isLecturer = $employee->employee_type === EmployeeType::Lecturer;
            $joined = $employee->joined_at ?? CarbonImmutable::create(2015, 1, 1);

            if (! EmployeeEducation::query()->where('employee_id', $employee->id)->exists()) {
                $levels = $isLecturer
                    ? ($index % 3 === 0 ? [EducationLevel::S1, EducationLevel::S2, EducationLevel::S3] : [EducationLevel::S1, EducationLevel::S2])
                    : [match ($index % 4) {
                        0 => EducationLevel::D3, 1 => EducationLevel::HighSchool, default => EducationLevel::S1
                    }];

                foreach ($levels as $step => $level) {
                    $graduated = $joined->year - (count($levels) - $step) * 2;

                    EmployeeEducation::query()->create([
                        'university_id' => $university->id,
                        'employee_id' => $employee->id,
                        'level' => $level,
                        'institution' => ['Universitas Indonesia', 'Universitas Gadjah Mada', 'Institut Teknologi Bandung', 'Universitas Airlangga', 'Universitas Padjadjaran'][($index + $step) % 5],
                        'major' => $isLecturer ? (self::EXPERTISE[$index % count(self::EXPERTISE)]) : ($level === EducationLevel::HighSchool ? 'IPA' : 'Administrasi'),
                        'entry_year' => $graduated - ($level === EducationLevel::S1 ? 4 : 2),
                        'graduation_year' => $graduated,
                        'certificate_number' => sprintf('IJZ/%d/%05d', $graduated, $index * 10 + $step),
                    ]);
                }

                $employee->highest_education = end($levels);
                $employee->saveQuietly();
            }

            if ($employee->position_id !== null && ! EmployeePosition::query()->where('employee_id', $employee->id)->exists()) {
                $position = Position::query()->find($employee->position_id);

                if ($position !== null) {
                    EmployeePosition::query()->create([
                        'university_id' => $university->id,
                        'employee_id' => $employee->id,
                        'position_id' => $position->id,
                        'position_name' => $position->name,
                        'position_type' => $position->type,
                        'work_unit_id' => $employee->work_unit_id,
                        'work_unit_name' => $employee->unit_kerja,
                        'start_date' => $joined->toDateString(),
                        'decree_number' => sprintf('SK/%d/%04d', $joined->year, $index),
                        'is_current' => true,
                    ]);
                }
            }

            if ($employee->employment_status === EmploymentStatus::Permanent && $ranks->isNotEmpty()
                && ! EmployeeRank::query()->where('employee_id', $employee->id)->exists()) {
                $rank = $ranks[min($index % 6, $ranks->count() - 1)];

                EmployeeRank::query()->create([
                    'university_id' => $university->id,
                    'employee_id' => $employee->id,
                    'rank_id' => $rank->id,
                    'rank_name' => $rank->name,
                    'grade' => $rank->grade,
                    'decree_number' => sprintf('SK-PKT/%d/%04d', $joined->year + 4, $index),
                    'decree_date' => $joined->addYears(4)->toDateString(),
                    'start_date' => $joined->addYears(4)->toDateString(),
                    'is_current' => true,
                ]);

                $employee->rank_id = $rank->id;
                $employee->saveQuietly();
            }

            if ($isLecturer && $employee->lecturer !== null && ! LecturerAcademicRank::query()->where('employee_id', $employee->id)->exists()) {
                $academicRank = [AcademicRank::AssistantExpert, AcademicRank::Lecturer, AcademicRank::Lecturer, AcademicRank::HeadLecturer, AcademicRank::Professor][$index % 5];

                LecturerAcademicRank::query()->create([
                    'university_id' => $university->id,
                    'employee_id' => $employee->id,
                    'academic_rank' => $academicRank,
                    'credit_points' => [150, 200, 300, 400, 850][$index % 5],
                    'decree_number' => sprintf('SK-JAB/%d/%04d', $joined->year + 2, $index),
                    'start_date' => $joined->addYears(2)->toDateString(),
                    'is_current' => true,
                ]);

                $employee->lecturer->academic_rank = $academicRank;
                $employee->lecturer->saveQuietly();

                if ($index % 2 === 0) {
                    LecturerActivity::query()->create([
                        'university_id' => $university->id,
                        'employee_id' => $employee->id,
                        'type' => LecturerActivityType::Research,
                        'title' => 'Penelitian '.self::EXPERTISE[$index % count(self::EXPERTISE)].' Berbasis Data',
                        'role' => 'Ketua',
                        'year' => 2025,
                        'funding_source' => 'Hibah Internal',
                        'amount' => 15000000,
                    ]);
                    LecturerActivity::query()->create([
                        'university_id' => $university->id,
                        'employee_id' => $employee->id,
                        'type' => LecturerActivityType::CommunityService,
                        'title' => 'Pelatihan Literasi Digital untuk UMKM',
                        'role' => 'Anggota',
                        'year' => 2025,
                    ]);
                }
            }
        });
    }

    private function seedContracts(University $university): void
    {
        $index = 0;
        $today = CarbonImmutable::today();

        Employee::query()
            ->whereIn('employment_status', [EmploymentStatus::Contract, EmploymentStatus::Honorary])
            ->orderBy('nip')
            ->each(function (Employee $employee) use ($university, $today, &$index): void {
                $index++;

                if (EmployeeContract::query()->where('employee_id', $employee->id)->exists()) {
                    return;
                }

                // Sebagian sengaja berakhir < 90 hari lagi, supaya alert
                // "Kontrak akan berakhir" di dashboard langsung terisi.
                $daysLeft = [12, 45, 80, 150, 240, 330][$index % 6];

                EmployeeContract::query()->create([
                    'university_id' => $university->id,
                    'employee_id' => $employee->id,
                    'contract_number' => sprintf('PKWT/%s/%d/%04d', Str::upper(Str::substr($university->code, 0, 4)), $today->year, $index),
                    'contract_type' => $employee->employment_status === EmploymentStatus::Honorary ? ContractType::Honorary : ContractType::Pkwt,
                    'start_date' => $today->addDays($daysLeft)->subYear()->toDateString(),
                    'end_date' => $today->addDays($daysLeft)->toDateString(),
                    'status' => ContractStatus::Active,
                    'notes' => 'Data contoh.',
                ]);
            });
    }

    private function seedDevelopment(University $university): void
    {
        if (EmployeeTraining::query()->exists()) {
            return;
        }

        $trainings = [
            ['Pelatihan Pelayanan Prima', 'Biro SDM', 16, TrainingStatus::Completed, 1500000],
            ['Workshop Penyusunan RPS', 'LPPM', 8, TrainingStatus::Completed, 750000],
            ['Seminar Nasional Pendidikan Tinggi', 'APTISI', 6, TrainingStatus::Completed, 500000],
            ['Pelatihan Keselamatan Kerja Laboratorium', 'K3 Nasional', 24, TrainingStatus::Planned, 2500000],
        ];
        $certifications = [
            ['Sertifikasi Pendidik', 'Kemendikbudristek', null],
            ['Certified Information Systems Auditor', 'ISACA', 3],
            ['Sertifikasi Kompetensi Pustakawan', 'BNSP', 1],
        ];

        Employee::query()->orderBy('nip')->limit(12)->get()->each(function (Employee $employee, int $index) use ($university, $trainings, $certifications): void {
            [$name, $organizer, $hours, $status, $cost] = $trainings[$index % count($trainings)];
            $start = CarbonImmutable::today()->subMonths(($index % 6) + 1);

            EmployeeTraining::query()->create([
                'university_id' => $university->id,
                'employee_id' => $employee->id,
                'name' => $name,
                'organizer' => $organizer,
                'start_date' => ($status === TrainingStatus::Planned ? CarbonImmutable::today()->addMonth() : $start)->toDateString(),
                'duration_hours' => $hours,
                'location' => 'Kampus Utama',
                'cost' => $cost,
                'status' => $status,
            ]);

            if ($index % 2 === 0) {
                [$certName, $issuer, $years] = $certifications[$index % count($certifications)];
                $issued = CarbonImmutable::today()->subYears(2)->subDays($index * 3);

                EmployeeCertification::query()->create([
                    'university_id' => $university->id,
                    'employee_id' => $employee->id,
                    'name' => $certName,
                    'issuer' => $issuer,
                    'certificate_number' => sprintf('CERT-%05d', $index + 1),
                    'issued_at' => $issued->toDateString(),
                    // Sebagian kedaluwarsa dalam 30 hari → alert dokumen.
                    'expires_at' => $years === null ? null : ($index === 2 ? CarbonImmutable::today()->addDays(20)->toDateString() : $issued->addYears($years)->toDateString()),
                ]);
            }
        });
    }

    private function seedPerformance(University $university): void
    {
        if (PerformanceReview::query()->where('period', '2025')->exists()) {
            return;
        }

        $scores = [94, 88, 81, 77, 69, 58, 91, 84];

        Employee::query()->orderBy('nip')->limit(16)->get()->each(function (Employee $employee, int $index) use ($university, $scores): void {
            $score = $scores[$index % count($scores)];

            PerformanceReview::query()->create([
                'university_id' => $university->id,
                'employee_id' => $employee->id,
                'period' => '2025',
                'score' => $score,
                'category' => PerformanceCategory::fromScore($score),
                'status' => $index % 4 === 0 ? PerformanceStatus::Draft : PerformanceStatus::Final,
                'notes' => 'Penilaian tahunan (data contoh).',
            ]);
        });
    }

    /**
     * Metadata dokumen + satu berkas PDF contoh di disk privat (bukan
     * public). Berkas ditautkan ke record dokumen supaya aksesnya mengikuti
     * aturan SDM (RestrictsFileAccess).
     */
    private function seedDocuments(University $university, User $uploader): void
    {
        if (EmployeeDocument::query()->exists()) {
            return;
        }

        $disk = (string) config('filesystems.default');
        $path = "uploads/seed/{$university->id}/contoh-dokumen.pdf";
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj "
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
        Storage::disk($disk)->put($path, $pdf);

        $samples = [
            [DocumentType::Ktp, DocumentStatus::Valid, null],
            [DocumentType::Diploma, DocumentStatus::Pending, null],
            [DocumentType::AppointmentDecree, DocumentStatus::Valid, null],
            [DocumentType::Certificate, DocumentStatus::Valid, 25],
        ];

        Employee::query()->orderBy('nip')->limit(6)->get()->each(function (Employee $employee, int $index) use ($university, $uploader, $disk, $path, $pdf, $samples): void {
            [$type, $status, $expiresInDays] = $samples[$index % count($samples)];

            $file = FileUpload::query()->create([
                'university_id' => $university->id,
                'uploaded_by' => $uploader->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => Str::slug($type->label()).'-'.Str::slug($employee->name).'.pdf',
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'size_bytes' => strlen($pdf),
                'checksum' => hash('sha256', $pdf),
                'status' => FileUploadStatus::Clean,
                'is_public' => false,
            ]);

            $document = EmployeeDocument::query()->create([
                'university_id' => $university->id,
                'employee_id' => $employee->id,
                'document_type' => $type,
                'title' => $type->label().' — '.$employee->name,
                'file_upload_id' => $file->id,
                'issued_at' => CarbonImmutable::today()->subYear()->toDateString(),
                'expires_at' => $expiresInDays !== null ? CarbonImmutable::today()->addDays($expiresInDays)->toDateString() : null,
                'status' => $status,
                'verified_by' => $status === DocumentStatus::Valid ? $uploader->id : null,
                'verified_at' => $status === DocumentStatus::Valid ? now() : null,
            ]);

            $file->forceFill(['fileable_type' => $document->getMorphClass(), 'fileable_id' => $document->id])->saveQuietly();
        });
    }

    /**
     * Contoh cuti & pengajuan. Yang sudah diputuskan ditulis sebagai data
     * historis; yang masih menunggu dibuat lewat engine ApprovalWorkflow
     * sungguhan supaya bisa langsung disetujui/ditolak dari aplikasi.
     */
    private function seedLeaveAndRequests(University $university, User $hrAdmin): void
    {
        $employee = Employee::query()->whereNotNull('user_id')->where('employee_type', EmployeeType::Staff)->first();
        $lecturer = Employee::query()->whereNotNull('user_id')->where('employee_type', EmployeeType::Lecturer)->first();

        if ($employee === null || LeaveRequest::query()->exists() || HrRequest::query()->exists()) {
            return;
        }

        $requester = $employee->user ?? $hrAdmin;
        $lastMonth = CarbonImmutable::today()->subMonth()->startOfWeek();

        $approvedLeave = LeaveRequest::query()->create([
            'university_id' => $university->id,
            'employee_id' => $employee->id,
            'leave_type' => LeaveType::Annual,
            'start_date' => $lastMonth->toDateString(),
            'end_date' => $lastMonth->addDays(2)->toDateString(),
            'days' => 3,
            'reason' => 'Acara keluarga.',
            'status' => LeaveStatus::Approved,
            'requested_by' => $requester->id,
            'submitted_at' => $lastMonth->subWeek(),
            'approved_by' => $hrAdmin->id,
            'approved_at' => $lastMonth->subDays(5),
            'approval_note' => 'Disetujui.',
        ]);

        HrRequest::query()->create([
            'university_id' => $university->id,
            'employee_id' => $employee->id,
            'type' => HrRequestType::Leave,
            'title' => 'Cuti Tahunan 3 hari',
            'description' => 'Acara keluarga.',
            'leave_request_id' => $approvedLeave->id,
            'status' => HrRequestStatus::Approved,
            'requested_by' => $requester->id,
            'approved_by' => $hrAdmin->id,
            'approved_at' => $lastMonth->subDays(5),
            'approval_note' => 'Disetujui.',
        ]);

        if ($lecturer !== null) {
            HrRequest::query()->create([
                'university_id' => $university->id,
                'employee_id' => $lecturer->id,
                'type' => HrRequestType::Promotion,
                'title' => 'Pengajuan kenaikan jabatan akademik ke Lektor Kepala',
                'description' => 'Angka kredit sudah memenuhi.',
                'status' => HrRequestStatus::Approved,
                'requested_by' => $lecturer->user_id ?? $hrAdmin->id,
                'approved_by' => $hrAdmin->id,
                'approved_at' => now()->subDays(3),
                'approval_note' => 'Silakan diproses SK-nya.',
            ]);
        }

        $nextWeek = CarbonImmutable::today()->addWeek()->startOfWeek();

        $this->withModelEvents(function () use ($employee, $requester, $nextWeek): void {
            app(LeaveService::class)->create($employee, [
                'leave_type' => LeaveType::Annual->value,
                'start_date' => $nextWeek->toDateString(),
                'end_date' => $nextWeek->addDays(1)->toDateString(),
                'reason' => 'Mengurus dokumen keluarga.',
            ], $requester, submit: true);

            app(HrRequestService::class)->submit($employee, HrRequestType::DataChange, [
                'title' => 'Perubahan nomor telepon',
                'description' => 'Nomor lama sudah tidak aktif.',
                'payload' => ['changes' => ['phone' => '081311112222']],
            ], $requester);

            app(HrNotificationService::class)->ensureTemplates();
        });
    }

    // ---- Helper ----------------------------------------------------------

    private function staffCategoryFor(string $unitName): StaffCategory
    {
        $name = Str::lower($unitName);

        return match (true) {
            str_contains($name, 'perpustakaan') => StaffCategory::Librarian,
            str_contains($name, 'lab') => StaffCategory::Laboratory,
            str_contains($name, 'teknologi') || str_contains($name, 'tik') => StaffCategory::InformationTechnology,
            str_contains($name, 'umum') => StaffCategory::Technician,
            default => StaffCategory::Administration,
        };
    }

    private function userWithRole(string $slug): ?User
    {
        $roleId = Role::query()->where('slug', $slug)->whereNull('university_id')->value('id');

        if ($roleId === null) {
            return null;
        }

        $userId = UserRole::query()
            ->where('role_id', $roleId)
            ->where('university_id', app(TenantContext::class)->universityId())
            ->orderBy('assigned_at')
            ->value('user_id');

        return $userId !== null ? User::query()->find($userId) : null;
    }

    /**
     * DatabaseSeeder mematikan model event (WithoutModelEvents); engine
     * ApprovalWorkflow mengandalkan event TenantScoped untuk mengisi
     * university_id. Event dinyalakan sementara hanya untuk blok ini, dan
     * notifikasi dijalankan sinkron ke kanal database (tanpa bergantung pada
     * Redis/SMTP lokal saat seeding).
     */
    private function withModelEvents(callable $callback): void
    {
        $previousDispatcher = Model::getEventDispatcher();
        $previousQueue = config('queue.default');
        $previousMailer = config('mail.default');

        Model::setEventDispatcher(app('events'));
        config(['queue.default' => 'sync', 'mail.default' => 'array']);

        try {
            $callback();
        } finally {
            config(['queue.default' => $previousQueue, 'mail.default' => $previousMailer]);

            if ($previousDispatcher !== null) {
                Model::setEventDispatcher($previousDispatcher);
            } else {
                Model::unsetEventDispatcher();
            }
        }
    }
}
