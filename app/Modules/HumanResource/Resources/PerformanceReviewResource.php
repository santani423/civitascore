<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\PerformanceReview;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin PerformanceReview
 */
class PerformanceReviewResource extends JsonResource
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
            'reviewer_employee_id' => $this->reviewer_employee_id,
            'reviewer' => $this->employeeSummary('reviewer'),
            'period' => $this->period,
            'score' => $this->score,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
