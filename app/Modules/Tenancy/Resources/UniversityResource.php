<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\University;

/**
 * @mixin University
 */
class UniversityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'legal_name' => $this->legal_name,
            'education_institution_type' => $this->education_institution_type,
            'accreditation' => $this->accreditation,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'status' => $this->status->value,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'currency' => $this->currency,
            'date_format' => $this->date_format,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'is_active' => $this->is_active,
            'activated_at' => $this->activated_at?->toIso8601String(),
            'suspended_at' => $this->suspended_at?->toIso8601String(),
            'domains' => $this->whenLoaded('domains', fn () => $this->domains->pluck('domain')),
            'active_subscription' => $this->whenLoaded('subscriptions', fn () => $this->subscriptions->first() ? [
                'plan' => $this->subscriptions->first()->plan?->name,
                'status' => $this->subscriptions->first()->status->value,
                'current_period_ends_at' => $this->subscriptions->first()->current_period_ends_at?->toIso8601String(),
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
