<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\EmployeeRank;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin EmployeeRank
 */
class EmployeeRankResource extends JsonResource
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
            'rank_id' => $this->rank_id,
            'rank_name' => $this->rank_name,
            'grade' => $this->grade,
            'decree_number' => $this->decree_number,
            'decree_date' => $this->decree_date?->toDateString(),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'decree_file_id' => $this->decree_file_id,
            'decree_file' => $this->fileSummary('decreeFile'),
            'is_current' => $this->is_current,
        ];
    }
}
