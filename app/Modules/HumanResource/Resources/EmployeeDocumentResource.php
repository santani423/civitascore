<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\EmployeeDocument;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin EmployeeDocument
 */
class EmployeeDocumentResource extends JsonResource
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
            'document_type' => $this->document_type->value,
            'document_type_label' => $this->document_type->label(),
            'title' => $this->title,
            'document_number' => $this->document_number,
            'file_upload_id' => $this->file_upload_id,
            'file' => $this->fileSummary('file'),
            'issued_at' => $this->issued_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'is_expired' => $this->isExpired(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'verification_note' => $this->verification_note,
            'verified_by_name' => $this->whenLoaded('verifier', fn () => $this->verifier?->name),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'version' => $this->version,
            'previous_version_id' => $this->previous_version_id,
            'is_current' => $this->is_current,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
