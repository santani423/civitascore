<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\ContractStatus;
use Modules\HumanResource\Enums\ContractType;
use Modules\HumanResource\Enums\DocumentStatus;
use Modules\HumanResource\Enums\DocumentType;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Enums\Gender;
use Modules\HumanResource\Enums\HasLabel;
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
use Modules\HumanResource\Enums\TransferStatus;
use Modules\HumanResource\Enums\WorkUnitType;
use Modules\HumanResource\Models\Position;
use Modules\HumanResource\Models\Rank;
use Modules\HumanResource\Models\WorkUnit;

/**
 * Satu sumber pilihan dropdown form SDM: label enum (bahasa Indonesia,
 * satu tempat — frontend tidak menduplikasi) + master aktif milik
 * universitas ini.
 */
class HrOptionsController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'enums' => [
                'employee_type' => $this->enum(EmployeeType::cases()),
                'employment_status' => $this->enum(EmploymentStatus::cases()),
                'gender' => $this->enum(Gender::cases()),
                'education_level' => $this->enum(EducationLevel::cases()),
                'staff_category' => $this->enum(StaffCategory::cases()),
                'work_unit_type' => $this->enum(WorkUnitType::cases()),
                'position_type' => $this->enum(PositionType::cases()),
                'academic_rank' => $this->enum(AcademicRank::cases()),
                'lecturer_status' => $this->enum(LecturerStatus::cases()),
                'lecturer_activity_type' => $this->enum(LecturerActivityType::cases()),
                'contract_type' => $this->enum(ContractType::cases()),
                'contract_status' => $this->enum(ContractStatus::cases()),
                'document_type' => $this->enum(DocumentType::cases()),
                'document_status' => $this->enum(DocumentStatus::cases()),
                'training_status' => $this->enum(TrainingStatus::cases()),
                'performance_category' => $this->enum(PerformanceCategory::cases()),
                'performance_status' => $this->enum(PerformanceStatus::cases()),
                'transfer_status' => $this->enum(TransferStatus::cases()),
                'leave_type' => $this->enum(LeaveType::cases()),
                'leave_status' => $this->enum(LeaveStatus::cases()),
                'request_type' => $this->enum(HrRequestType::cases()),
                'request_status' => $this->enum(HrRequestStatus::cases()),
            ],
            'work_units' => WorkUnit::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'type'])
                ->map(fn (WorkUnit $unit): array => ['value' => $unit->id, 'label' => $unit->name]),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'type'])
                ->map(fn (Position $position): array => ['value' => $position->id, 'label' => $position->name, 'type' => $position->type->value]),
            'ranks' => Rank::query()->where('is_active', true)->orderBy('level')->get(['id', 'name', 'grade'])
                ->map(fn (Rank $rank): array => ['value' => $rank->id, 'label' => "{$rank->grade} — {$rank->name}"]),
            'faculties' => Faculty::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Faculty $faculty): array => ['value' => $faculty->id, 'label' => $faculty->name]),
            'study_programs' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'faculty_id'])
                ->map(fn (StudyProgram $program): array => ['value' => $program->id, 'label' => $program->name, 'faculty_id' => $program->faculty_id]),
        ]);
    }

    /**
     * @param  array<int, HasLabel&\BackedEnum>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function enum(array $cases): array
    {
        return array_map(fn (HasLabel&\BackedEnum $case): array => ['value' => (string) $case->value, 'label' => $case->label()], $cases);
    }
}
