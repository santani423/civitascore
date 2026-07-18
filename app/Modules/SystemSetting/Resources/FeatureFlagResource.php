<?php

namespace Modules\SystemSetting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SystemSetting\Models\FeatureFlag;

/**
 * @mixin FeatureFlag
 */
class FeatureFlagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'is_enabled' => $this->is_enabled,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
