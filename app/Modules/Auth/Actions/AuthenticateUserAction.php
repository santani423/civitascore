<?php

namespace Modules\Auth\Actions;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Student;
use Modules\Auth\Enums\LoginFailureReason;
use Modules\Auth\Models\UserSession;
use Modules\Auth\Services\SessionTrackingService;
use Modules\SystemSetting\Services\SystemSettingService;
use Modules\Tenancy\Models\University;

class AuthenticateUserAction
{
    public function __construct(
        private readonly SessionTrackingService $sessions,
        private readonly SystemSettingService $settings,
        private readonly TenantContext $tenant,
    ) {}

    /**
     * @return array{user: User, token: string, session: UserSession}
     */
    public function execute(
        ?string $email,
        string $password,
        Request $request,
        ?string $deviceIdentifier = null,
        ?string $nim = null,
        ?string $universityCode = null,
    ): array {
        if ($nim !== null) {
            return $this->executeStudentLogin($nim, $password, $request, $deviceIdentifier, $universityCode);
        }

        return $this->executeEmailLogin((string) $email, $password, $request, $deviceIdentifier);
    }

    /**
     * @return array{user: User, token: string, session: UserSession}
     */
    private function executeEmailLogin(string $email, string $password, Request $request, ?string $deviceIdentifier): array
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

        return $this->issueSession($user, $request, $deviceIdentifier);
    }

    /**
     * NIM is only unique per (university_id, nim) — see the `students`
     * migration — so this must never look a student up without a resolved
     * tenant. If the tenant can't be resolved (no X-University-ID header,
     * no matching custom domain, and no university_code fallback in the
     * request), the login is rejected rather than falling back to an
     * unscoped/global lookup.
     *
     * @return array{user: User, token: string, session: UserSession}
     */
    private function executeStudentLogin(
        string $nim,
        string $password,
        Request $request,
        ?string $deviceIdentifier,
        ?string $universityCode,
    ): array {
        $throttleKey = 'login:nim:'.mb_strtolower($nim).'|'.$request->ip();
        $maxAttempts = (int) $this->settings->get('security.max_login_attempts', 5);
        $genericFailure = fn () => throw ValidationException::withMessages([
            'nim' => ['NIM atau password tidak sesuai.'],
        ]);

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $this->sessions->recordFailedAttempt(null, $nim, $request, LoginFailureReason::TooManyAttempts);

            throw ValidationException::withMessages([
                'nim' => ['Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.'],
            ])->status(429);
        }

        $universityId = $this->resolveUniversityId($universityCode);

        if ($universityId === null) {
            RateLimiter::hit($throttleKey, 60);
            $this->sessions->recordFailedAttempt(null, $nim, $request, LoginFailureReason::InvalidCredentials);
            $genericFailure();
        }

        $student = Student::query()
            ->where('university_id', $universityId)
            ->where('nim', $nim)
            ->first();

        $user = $student?->user_id ? $student->user : null;

        if (! $student || ! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            $this->sessions->recordFailedAttempt($user, $nim, $request, LoginFailureReason::InvalidCredentials);
            $genericFailure();
        }

        if (! $user->is_active || in_array($student->status, [StudentStatus::Inactive, StudentStatus::DroppedOut], true)) {
            $this->sessions->recordFailedAttempt($user, $nim, $request, LoginFailureReason::AccountInactive);

            throw ValidationException::withMessages([
                'nim' => ['Akun Anda tidak aktif.'],
            ])->status(403);
        }

        RateLimiter::clear($throttleKey);

        return $this->issueSession($user, $request, $deviceIdentifier);
    }

    private function resolveUniversityId(?string $universityCode): ?string
    {
        if ($this->tenant->hasUniversity()) {
            return $this->tenant->universityId();
        }

        if ($universityCode === null) {
            return null;
        }

        return University::query()->where('code', $universityCode)->value('id');
    }

    /**
     * @return array{user: User, token: string, session: UserSession}
     */
    private function issueSession(User $user, Request $request, ?string $deviceIdentifier): array
    {
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
