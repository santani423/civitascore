<?php

namespace Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\UserSession;

class LogoutUserAction
{
    public function execute(User $user, string $tokenId): void
    {
        DB::transaction(function () use ($user, $tokenId): void {
            UserSession::query()
                ->where('user_id', $user->id)
                ->where('session_token', $tokenId)
                ->update(['revoked_at' => now(), 'revoked_by' => $user->id]);

            $user->tokens()->where('id', $tokenId)->delete();
        });
    }

    public function executeAllDevices(User $user): void
    {
        DB::transaction(function () use ($user): void {
            UserSession::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'revoked_by' => $user->id]);

            $user->tokens()->delete();
        });
    }
}
