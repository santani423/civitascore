<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\EmployeePosition;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin EmployeePosition
 */
class EmployeePositionResource extends JsonResource
{
    use PresentsHrRelations;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->employeeSummary(),
            'position_id' => $this->position_id,
            'position_name' => $this->position_name,
            'position_type' => $this->position_type->value,
            'position_type_label' => $this->position_type->label(),
            'work_unit_id' => $this->work_unit_id,
            'work_unit_name' => $this->work_unit_name,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'decree_number' => $this->decree_number,
            'decree_file_id' => $this->decree_file_id,
            'decree_file' => $this->fileSummary('decreeFile'),
            'notes' => $this->notes,
            'is_current' => $this->is_current,
        ];
    }
}
