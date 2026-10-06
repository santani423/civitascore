<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\EmployeeCertification;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin EmployeeCertification
 */
class EmployeeCertificationResource extends JsonResource
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
            'issuer' => $this->issuer,
            'certificate_number' => $this->certificate_number,
            'issued_at' => $this->issued_at->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'is_expired' => $this->expires_at?->isPast() ?? false,
            'document_file_id' => $this->document_file_id,
            'document_file' => $this->fileSummary('documentFile'),
            'notes' => $this->notes,
        ];
    }
}
