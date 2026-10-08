<?php

namespace Modules\SystemSetting\Resources;

use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SystemSetting\Models\FeatureFlag;
use Modules\SystemSetting\Services\FeatureFlagService;

/**
 * `is_enabled` adalah nilai efektif untuk konteks permintaan: di konteks
 * tenant = override universitas itu bila ada, selain itu nilai global;
 * `default_enabled` selalu nilai global dan `university_override` null
 * bila universitas mengikuti nilai global (atau di konteks platform).
 *
 * @mixin FeatureFlag
 */
class FeatureFlagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $universityId = app(TenantContext::class)->universityId();
        $override = $universityId === null ? null : app(FeatureFlagService::class)->overrideFor($this->key, $universityId);

        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            // Yang diubah PUT feature-flags/{id} pada konteks permintaan ini.
            'scope' => $universityId === null ? 'global' : 'university',
            'is_enabled' => $override ?? $this->is_enabled,
            'default_enabled' => $this->is_enabled,
            'university_override' => $override,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
