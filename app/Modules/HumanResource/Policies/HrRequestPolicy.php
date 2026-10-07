<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Enums\HrRequestType;
use Modules\HumanResource\Models\HrRequest;

/**
 * Pengajuan SDM. Permission di sini menentukan siapa yang boleh membuka
 * halaman & memanggil aksinya; siapa yang boleh memutuskan langkah
 * persetujuan tertentu tetap ditentukan ApprovalRequestStepPolicy (dicek
 * di HrRequestService::actionableStep()).
 */
class HrRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_requests.read');
    }

    public function view(User $user, HrRequest $hrRequest): bool
    {
        return $user->hasPermissionTo('hr_requests.read')
            || ($hrRequest->type === HrRequestType::Leave && $user->hasPermissionTo('hr_leave.read'))
            || $this->owns($user, $hrRequest);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_requests.create');
    }

    public function approve(User $user, HrRequest $hrRequest): bool
    {
        return $user->hasPermissionTo('hr_requests.approve')
            || ($hrRequest->type === HrRequestType::Leave && $user->hasPermissionTo('hr_leave.approve'));
    }

    public function reject(User $user, HrRequest $hrRequest): bool
    {
        return $user->hasPermissionTo('hr_requests.reject')
            || ($hrRequest->type === HrRequestType::Leave && $user->hasPermissionTo('hr_leave.reject'));
    }

    public function process(User $user, HrRequest $hrRequest): bool
    {
        return $user->hasPermissionTo('hr_requests.update');
    }

    public function cancel(User $user, HrRequest $hrRequest): bool
    {
        return $this->owns($user, $hrRequest) || $user->hasPermissionTo('hr_requests.update');
    }

    private function owns(User $user, HrRequest $hrRequest): bool
    {
        $ownerUserId = $hrRequest->employee?->user_id;

        return ($ownerUserId !== null && $ownerUserId === $user->id) || $hrRequest->requested_by === $user->id;
    }
}
