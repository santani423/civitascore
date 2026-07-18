<?php

namespace Modules\SystemSetting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SystemSetting\Models\SystemSetting;

/**
 * @mixin SystemSetting
 */
class SystemSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->castedValue(),
            'type' => $this->type->value,
            'group' => $this->group,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
