<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\Rank;

/**
 * @mixin Rank
 */
class RankResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'grade' => $this->grade,
            'level' => $this->level,
            'is_active' => $this->is_active,
            'employees_count' => $this->whenCounted('employees'),
        ];
    }
}
