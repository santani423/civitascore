<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\SupportSession;

/**
 * @mixin SupportSession
 */
class SupportSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'super_admin_id' => $this->super_admin_id,
            'super_admin_name' => $this->superAdmin?->name,
            'university_id' => $this->university_id,
            'university_name' => $this->university?->name,
            'reason' => $this->reason,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
