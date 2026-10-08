<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Employee;

/**
 * Baris list Data Pegawai (Modul SDM) — terpisah dari
 * Modules\Academic\Resources\EmployeeResource yang dipakai menu Pegawai
 * lama. NIK tidak pernah ada di list, hanya di detail.
 *
 * @mixin Employee
 */
class HrEmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_type' => $this->employee_type->value,
            'employee_type_label' => $this->employee_type->label(),
            'nip' => $this->nip,
            'nidn' => $this->whenLoaded('lecturer', fn () => $this->lecturer?->nidn),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'unit_kerja' => $this->unit_kerja,
            'work_unit_id' => $this->work_unit_id,
            'position' => $this->position,
            'position_id' => $this->position_id,
            'employment_status' => $this->employment_status->value,
            'employment_status_label' => $this->employment_status->label(),
            'highest_education' => $this->highest_education?->value,
            'highest_education_label' => $this->highest_education?->label(),
            'staff_category' => $this->staff_category?->value,
            'staff_category_label' => $this->staff_category?->label(),
            'assigned_facility' => $this->assigned_facility,
            'faculty_id' => $this->faculty_id,
            'faculty_name' => $this->whenLoaded('faculty', fn () => $this->faculty?->name),
            'study_program_id' => $this->study_program_id,
            'study_program_name' => $this->whenLoaded('studyProgram', fn () => $this->studyProgram?->name),
            'academic_rank' => $this->whenLoaded('lecturer', fn () => $this->lecturer?->academic_rank?->value),
            'academic_rank_label' => $this->whenLoaded('lecturer', fn () => $this->lecturer?->academic_rank?->label()),
            'joined_at' => $this->joined_at?->toDateString(),
            'is_active' => $this->is_active,
            'has_account' => $this->user_id !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
