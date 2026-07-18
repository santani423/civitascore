<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Modules\Auth\Actions\AuthenticateUserAction;
use Modules\Auth\Actions\LogoutUserAction;
use Modules\Auth\Requests\ForgotPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\ResetPasswordRequest;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticateUserAction $authenticate,
        private readonly LogoutUserAction $logout,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authenticate->execute(
            $request->validated('email'),
            $request->validated('password'),
            $request,
            $request->validated('device_identifier'),
        );

        return ApiResponse::success([
            'token' => $result['token'],
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
            ],
        ], 'Login berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokenId = $user->currentAccessToken()->getKey();

        $this->logout->execute($user, $tokenId);

        return ApiResponse::success(message: 'Berhasil keluar.');
    }

    public function logoutAllDevices(Request $request): JsonResponse
    {
        $this->logout->executeAllDevices($request->user());

        return ApiResponse::success(message: 'Berhasil keluar dari seluruh perangkat.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return ApiResponse::error('Gagal mengirim tautan reset password.', status: 422);
        }

        return ApiResponse::success(message: 'Tautan reset password telah dikirim.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill(['password' => $password])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error('Token reset password tidak valid atau sudah kedaluwarsa.', status: 422);
        }

        return ApiResponse::success(message: 'Password berhasil diperbarui.');
    }
}
