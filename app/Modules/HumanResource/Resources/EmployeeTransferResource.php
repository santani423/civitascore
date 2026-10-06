<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\EmployeeTransfer;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin EmployeeTransfer
 */
class EmployeeTransferResource extends JsonResource
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
            'from_work_unit_id' => $this->from_work_unit_id,
            'from_work_unit_name' => $this->from_work_unit_name,
            'to_work_unit_id' => $this->to_work_unit_id,
            'to_work_unit_name' => $this->to_work_unit_name,
            'from_position_id' => $this->from_position_id,
            'from_position_name' => $this->from_position_name,
            'to_position_id' => $this->to_position_id,
            'to_position_name' => $this->to_position_name,
            'effective_date' => $this->effective_date->toDateString(),
            'decree_number' => $this->decree_number,
            'document_file_id' => $this->document_file_id,
            'document_file' => $this->fileSummary('documentFile'),
            'reason' => $this->reason,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'applied_at' => $this->applied_at?->toIso8601String(),
            'created_by_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
