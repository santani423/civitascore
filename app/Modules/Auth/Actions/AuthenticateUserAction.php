<?php

namespace Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\LoginFailureReason;
use Modules\Auth\Models\UserSession;
use Modules\Auth\Services\SessionTrackingService;
use Modules\SystemSetting\Services\SystemSettingService;

class AuthenticateUserAction
{
    public function __construct(
        private readonly SessionTrackingService $sessions,
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * @return array{user: User, token: string, session: UserSession}
     */
    public function execute(string $email, string $password, Request $request, ?string $deviceIdentifier = null): array
    {
        $throttleKey = 'login:'.mb_strtolower($email).'|'.$request->ip();
        $maxAttempts = (int) $this->settings->get('security.max_login_attempts', 5);

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $this->sessions->recordFailedAttempt(null, $email, $request, LoginFailureReason::TooManyAttempts);

            throw ValidationException::withMessages([
                'email' => ['Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.'],
            ])->status(429);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            $this->sessions->recordFailedAttempt($user, $email, $request, LoginFailureReason::InvalidCredentials);

            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            $this->sessions->recordFailedAttempt($user, $email, $request, LoginFailureReason::AccountInactive);

            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif.'],
            ])->status(403);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken($deviceIdentifier ?? 'api', ['*']);

        ['session' => $session] = $this->sessions->recordSuccessfulLogin(
            $user,
            $request,
            $deviceIdentifier,
            $token->accessToken->getKey(),
        );

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'session' => $session,
        ];
    }
}
