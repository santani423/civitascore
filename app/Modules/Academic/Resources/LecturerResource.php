<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Lecturer;

/**
 * @mixin Lecturer
 */
class LecturerResource extends JsonResource
{
    /**
     * Whether SDM may run account actions (reset password, activate/
     * deactivate) on the linked account — see
     * LecturerAccountService::isManageable(). Only computed on the detail
     * endpoint; null elsewhere.
     */
    private ?bool $accountManageable = null;

    public function withAccountManageable(?bool $manageable): static
    {
        $this->accountManageable = $manageable;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'faculty_id' => $this->faculty_id,
            'faculty_name' => $this->whenLoaded('faculty', fn () => $this->faculty?->name),
            'nidn' => $this->nidn,
            'nip' => $this->nip,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'employment_status' => $this->employment_status?->value,
            'functional_rank' => $this->functional_rank?->value,
            'highest_education' => $this->highest_education?->value,
            'hired_at' => $this->hired_at?->toDateString(),
            'is_active' => $this->is_active,
            'has_account' => $this->user_id !== null,
            'account' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'email' => $this->user->email,
                'is_active' => $this->user->is_active,
                'must_change_password' => $this->user->must_change_password,
                'password_changed_at' => $this->user->password_changed_at?->toIso8601String(),
                'manageable' => $this->accountManageable,
            ] : null),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
