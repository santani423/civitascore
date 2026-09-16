<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\ExamGradeRange;

/**
 * @mixin ExamGradeRange
 */
class ExamGradeRangeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grade' => $this->grade,
            'min_score' => $this->min_score,
            'max_score' => $this->max_score,
        ];
    }
}
