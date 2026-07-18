<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Auth\Enums\DeviceType;
use Modules\Auth\Enums\LoginFailureReason;
use Modules\Auth\Enums\LoginHistoryStatus;
use Modules\Auth\Events\NewDeviceDetected as NewDeviceDetectedEvent;
use Modules\Auth\Models\LoginHistory;
use Modules\Auth\Models\UserDevice;
use Modules\Auth\Models\UserSession;
use Modules\Auth\Notifications\NewDeviceDetected as NewDeviceDetectedNotification;

/**
 * Writes to the application-level security ledger (login_histories,
 * user_devices, user_sessions) independent of whichever low-level session
 * driver (database/redis) Laravel itself is configured to use.
 */
class SessionTrackingService
{
    public function recordFailedAttempt(?User $user, string $emailAttempted, Request $request, LoginFailureReason $reason): LoginHistory
    {
        return LoginHistory::create([
            'user_id' => $user?->id,
            'email_attempted' => $emailAttempted,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $reason === LoginFailureReason::TooManyAttempts ? LoginHistoryStatus::Blocked : LoginHistoryStatus::Failed,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * @return array{loginHistory: LoginHistory, session: UserSession, device: ?UserDevice}
     */
    public function recordSuccessfulLogin(User $user, Request $request, ?string $deviceIdentifier, string $tokenId): array
    {
        $device = $deviceIdentifier ? $this->resolveDevice($user, $deviceIdentifier, $request) : null;

        $loginHistory = LoginHistory::create([
            'user_id' => $user->id,
            'email_attempted' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_device_id' => $device?->id,
            'status' => LoginHistoryStatus::Success,
        ]);

        // session_token deliberately mirrors the Sanctum personal_access_token
        // id, so revoking/logging out a session can delete the exact token.
        $session = UserSession::create([
            'user_id' => $user->id,
            'user_device_id' => $device?->id,
            'login_history_id' => $loginHistory->id,
            'session_token' => $tokenId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity_at' => now(),
        ]);

        if ($device !== null && $device->wasRecentlyCreated) {
            $user->notify(new NewDeviceDetectedNotification($device));
            NewDeviceDetectedEvent::dispatch($user, $device);
        }

        return ['loginHistory' => $loginHistory, 'session' => $session, 'device' => $device];
    }

    public function revokeSession(UserSession $session, User $revokedBy): void
    {
        $session->update(['revoked_at' => now(), 'revoked_by' => $revokedBy->id]);
    }

    private function resolveDevice(User $user, string $deviceIdentifier, Request $request): UserDevice
    {
        $device = UserDevice::query()->firstOrCreate(
            ['user_id' => $user->id, 'device_identifier' => $deviceIdentifier],
            [
                'device_name' => $request->header('X-Device-Name', 'Unknown device'),
                'device_type' => DeviceType::Unknown,
                'platform' => $request->header('X-Device-Platform'),
                'last_used_at' => now(),
            ],
        );

        if (! $device->wasRecentlyCreated) {
            $device->update(['last_used_at' => now()]);
        }

        return $device;
    }
}
