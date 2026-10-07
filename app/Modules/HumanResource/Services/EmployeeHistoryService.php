<?php

namespace Modules\HumanResource\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Models\EmployeeEducation;
use Modules\HumanResource\Models\EmployeePosition;
use Modules\HumanResource\Models\EmployeeRank;
use Modules\HumanResource\Models\LecturerAcademicRank;
use Modules\HumanResource\Models\Position;
use Modules\HumanResource\Models\Rank;
use Modules\HumanResource\Models\WorkUnit;
use Modules\HumanResource\Services\Concerns\SavesRecordWithFiles;

/**
 * Riwayat jabatan, kepangkatan, jabatan akademik, dan pendidikan.
 *
 * Prinsip: riwayat tidak pernah ditimpa. Entri baru yang "berlaku saat
 * ini" menutup entri berlaku sebelumnya (end_date diisi, is_current =
 * false), lalu nilai aktif disalin ke employees/lecturers supaya list &
 * laporan tidak perlu menelusuri riwayat.
 */
class EmployeeHistoryService
{
    use SavesRecordWithFiles;

    // ---- Jabatan -------------------------------------------------------

    public function recordInitialPosition(Employee $employee): EmployeePosition
    {
        $position = Position::query()->findOrFail($employee->position_id);

        return EmployeePosition::query()->create([
            'university_id' => $employee->university_id,
            'employee_id' => $employee->id,
            'position_id' => $position->id,
            'position_name' => $position->name,
            'position_type' => $position->type,
            'work_unit_id' => $employee->work_unit_id,
            'work_unit_name' => $employee->work_unit_id !== null ? $employee->unit_kerja : null,
            'start_date' => ($employee->joined_at ?? CarbonImmutable::today())->toDateString(),
            'is_current' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function savePosition(Employee $employee, array $data, User $actor, ?EmployeePosition $record = null): EmployeePosition
    {
        $position = Position::query()->whereKey($data['position_id'])->firstOrFail();
        $unit = ! empty($data['work_unit_id']) ? WorkUnit::query()->whereKey($data['work_unit_id'])->firstOrFail() : null;
        $isCurrent = (bool) ($data['is_current'] ?? empty($data['end_date']));

        return DB::transaction(function () use ($employee, $data, $actor, $record, $position, $unit, $isCurrent): EmployeePosition {
            $record ??= new EmployeePosition(['employee_id' => $employee->id, 'university_id' => $employee->university_id]);

            if ($isCurrent) {
                $this->closeCurrent(
                    EmployeePosition::query()->where('employee_id', $employee->id)->where('position_type', $position->type->value),
                    $data['start_date'],
                    $record,
                );
            }

            $this->saveWithFiles($record, [
                ...$data,
                'position_name' => $position->name,
                'position_type' => $position->type,
                'work_unit_name' => $unit?->name,
                'is_current' => $isCurrent,
            ], ['decree_file_id'], $actor);

            $this->syncEmployeePosition($employee);

            return $record;
        });
    }

    public function deletePosition(EmployeePosition $record): void
    {
        DB::transaction(function () use ($record): void {
            $employee = $record->employee;
            $record->delete();

            if ($employee !== null) {
                $this->syncEmployeePosition($employee);
            }
        });
    }

    /**
     * Jabatan aktif pegawai = entri berlaku terbaru (struktural diutamakan
     * atas fungsional).
     */
    private function syncEmployeePosition(Employee $employee): void
    {
        $current = EmployeePosition::query()
            ->where('employee_id', $employee->id)
            ->where('is_current', true)
            ->orderByRaw("case when position_type = 'structural' then 0 else 1 end")
            ->orderByDesc('start_date')
            ->first();

        $employee->position_id = $current?->position_id;
        $employee->position = $current->position_name ?? ($employee->isLecturer() ? 'Dosen' : $employee->position);
        $employee->save();
    }

    // ---- Kepangkatan ---------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveRank(Employee $employee, array $data, User $actor, ?EmployeeRank $record = null): EmployeeRank
    {
        $rank = Rank::query()->whereKey($data['rank_id'])->firstOrFail();
        $isCurrent = (bool) ($data['is_current'] ?? empty($data['end_date']));

        return DB::transaction(function () use ($employee, $data, $actor, $record, $rank, $isCurrent): EmployeeRank {
            $record ??= new EmployeeRank(['employee_id' => $employee->id, 'university_id' => $employee->university_id]);

            if ($isCurrent) {
                $this->closeCurrent(EmployeeRank::query()->where('employee_id', $employee->id), $data['start_date'], $record);
            }

            $this->saveWithFiles($record, [
                ...$data,
                'rank_name' => $rank->name,
                'grade' => $rank->grade,
                'is_current' => $isCurrent,
            ], ['decree_file_id'], $actor);

            $this->syncEmployeeRank($employee);

            return $record;
        });
    }

    public function deleteRank(EmployeeRank $record): void
    {
        DB::transaction(function () use ($record): void {
            $employee = $record->employee;
            $record->delete();

            if ($employee !== null) {
                $this->syncEmployeeRank($employee);
            }
        });
    }

    private function syncEmployeeRank(Employee $employee): void
    {
        $employee->rank_id = EmployeeRank::query()
            ->where('employee_id', $employee->id)
            ->where('is_current', true)
            ->orderByDesc('start_date')
            ->value('rank_id');
        $employee->save();
    }

    // ---- Jabatan akademik (dosen) --------------------------------------

    /**
     * @param  array<string, mixed>  $data
     */
    public function addAcademicRank(Employee $employee, array $data, User $actor): LecturerAcademicRank
    {
        return $this->saveAcademicRank($employee, $data, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveAcademicRank(Employee $employee, array $data, User $actor, ?LecturerAcademicRank $record = null): LecturerAcademicRank
    {
        $this->assertLecturer($employee);
        $isCurrent = (bool) ($data['is_current'] ?? empty($data['end_date']));

        return DB::transaction(function () use ($employee, $data, $actor, $record, $isCurrent): LecturerAcademicRank {
            $record ??= new LecturerAcademicRank(['employee_id' => $employee->id, 'university_id' => $employee->university_id]);

            if ($isCurrent) {
                $this->closeCurrent(LecturerAcademicRank::query()->where('employee_id', $employee->id), $data['start_date'], $record);
            }

            $this->saveWithFiles($record, [...$data, 'is_current' => $isCurrent], ['decree_file_id'], $actor);
            $this->syncLecturerAcademicRank($employee);

            return $record;
        });
    }

    public function deleteAcademicRank(LecturerAcademicRank $record): void
    {
        DB::transaction(function () use ($record): void {
            $employee = $record->employee;
            $record->delete();

            if ($employee !== null) {
                $this->syncLecturerAcademicRank($employee);
            }
        });
    }

    private function syncLecturerAcademicRank(Employee $employee): void
    {
        $value = LecturerAcademicRank::query()
            ->where('employee_id', $employee->id)
            ->where('is_current', true)
            ->orderByDesc('start_date')
            ->value('academic_rank');

        $employee->lecturer?->update([
            'academic_rank' => $value instanceof AcademicRank ? $value : ($value !== null ? AcademicRank::from($value) : null),
        ]);
    }

    // ---- Pendidikan ----------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveEducation(Employee $employee, array $data, User $actor, ?EmployeeEducation $record = null): EmployeeEducation
    {
        return DB::transaction(function () use ($employee, $data, $actor, $record): EmployeeEducation {
            $record ??= new EmployeeEducation(['employee_id' => $employee->id, 'university_id' => $employee->university_id]);
            $this->saveWithFiles($record, $data, ['document_file_id'], $actor);
            $this->syncHighestEducation($employee);

            return $record;
        });
    }

    public function deleteEducation(EmployeeEducation $record): void
    {
        DB::transaction(function () use ($record): void {
            $employee = $record->employee;
            $record->delete();

            if ($employee !== null) {
                $this->syncHighestEducation($employee);
            }
        });
    }

    /**
     * "Pendidikan terakhir" = jenjang tertinggi di riwayat (urutan jenjang,
     * bukan urutan input). Kalau riwayat kosong, nilai yang diisi manual di
     * form pegawai dipertahankan.
     */
    private function syncHighestEducation(Employee $employee): void
    {
        $levels = EmployeeEducation::query()->where('employee_id', $employee->id)->get()->pluck('level');

        if ($levels->isEmpty()) {
            return;
        }

        $employee->highest_education = $levels->sortByDesc(fn (EducationLevel $level): int => $level->rank())->first();
        $employee->save();
    }

    // ---- Bersama -------------------------------------------------------

    /**
     * @param  Builder<EmployeePosition>|Builder<EmployeeRank>|Builder<LecturerAcademicRank>  $query
     */
    private function closeCurrent($query, string $newStartDate, ?Model $except): void
    {
        $closingDate = CarbonImmutable::parse($newStartDate)->subDay()->toDateString();

        $query->where('is_current', true)
            ->when($except !== null && $except->exists, fn ($builder) => $builder->whereKeyNot($except->getKey()))
            ->get()
            ->each(function ($previous) use ($closingDate): void {
                $previous->update([
                    'is_current' => false,
                    'end_date' => $previous->end_date ?? $closingDate,
                ]);
            });
    }

    private function assertLecturer(Employee $employee): void
    {
        if (! $employee->isLecturer()) {
            throw ValidationException::withMessages(['employee_id' => 'Data ini hanya berlaku untuk pegawai berjenis dosen.']);
        }
    }
}
