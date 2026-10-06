<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\LecturerAcademicRank;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin LecturerAcademicRank
 */
class LecturerAcademicRankResource extends JsonResource
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
            'academic_rank' => $this->academic_rank->value,
            'academic_rank_label' => $this->academic_rank->label(),
            'credit_points' => $this->credit_points,
            'decree_number' => $this->decree_number,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'decree_file_id' => $this->decree_file_id,
            'decree_file' => $this->fileSummary('decreeFile'),
            'is_current' => $this->is_current,
        ];
    }
}
