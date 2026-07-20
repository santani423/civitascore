<?php

namespace Modules\Scholarship\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Scholarship\Models\Scholarship;

/**
 * @mixin Scholarship
 */
class ScholarshipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'quota' => $this->quota,
            'amount' => $this->amount,
            'academic_year' => $this->academic_year,
            'registration_start' => $this->registration_start->toDateString(),
            'registration_end' => $this->registration_end->toDateString(),
            'is_active' => $this->is_active,
            'applications_count' => $this->whenCounted('applications'),
            'applications' => $this->whenLoaded('applications', fn () => ScholarshipApplicationResource::collection($this->applications)),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
