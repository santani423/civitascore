<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\EmployeeContract;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin EmployeeContract
 */
class EmployeeContractResource extends JsonResource
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
            'contract_number' => $this->contract_number,
            'contract_type' => $this->contract_type->value,
            'contract_type_label' => $this->contract_type->label(),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'days_remaining' => $this->daysRemaining(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'document_file_id' => $this->document_file_id,
            'document_file' => $this->fileSummary('documentFile'),
            'notes' => $this->notes,
            'terminated_at' => $this->terminated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
