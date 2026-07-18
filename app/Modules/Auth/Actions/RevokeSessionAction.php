<?php

namespace Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\UserSession;

class RevokeSessionAction
{
    public function execute(UserSession $session, User $revokedBy): void
    {
        DB::transaction(function () use ($session, $revokedBy): void {
            $session->update(['revoked_at' => now(), 'revoked_by' => $revokedBy->id]);
            $session->user->tokens()->where('id', $session->session_token)->delete();
        });
    }
}
