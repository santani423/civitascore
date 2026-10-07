<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\EmployeeTraining;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin EmployeeTraining
 */
class EmployeeTrainingResource extends JsonResource
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
            'name' => $this->name,
            'organizer' => $this->organizer,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'duration_hours' => $this->duration_hours,
            'location' => $this->location,
            'cost' => $this->cost,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'certificate_file_id' => $this->certificate_file_id,
            'certificate_file' => $this->fileSummary('certificateFile'),
            'notes' => $this->notes,
        ];
    }
}
