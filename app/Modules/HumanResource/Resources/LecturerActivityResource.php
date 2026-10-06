<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\LecturerActivity;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin LecturerActivity
 */
class LecturerActivityResource extends JsonResource
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
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'title' => $this->title,
            'role' => $this->role,
            'year' => $this->year,
            'funding_source' => $this->funding_source,
            'amount' => $this->amount,
            'description' => $this->description,
            'document_file_id' => $this->document_file_id,
            'document_file' => $this->fileSummary('documentFile'),
        ];
    }
}
