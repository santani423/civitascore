<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\EmployeeEducation;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin EmployeeEducation
 */
class EmployeeEducationResource extends JsonResource
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
            'level' => $this->level->value,
            'level_label' => $this->level->label(),
            'institution' => $this->institution,
            'major' => $this->major,
            'entry_year' => $this->entry_year,
            'graduation_year' => $this->graduation_year,
            'certificate_number' => $this->certificate_number,
            'gpa' => $this->gpa,
            'document_file_id' => $this->document_file_id,
            'document_file' => $this->fileSummary('documentFile'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
