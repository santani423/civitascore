<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\RevokeSessionAction;
use Modules\Auth\Models\UserSession;
use Modules\Auth\Resources\UserSessionResource;

class SessionController extends Controller
{
    public function __construct(private readonly RevokeSessionAction $revokeSession) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UserSession::class);

        $sessions = $request->user()->sessions()->with('device')->latest('last_activity_at')->get();

        return ApiResponse::success(UserSessionResource::collection($sessions));
    }

    public function destroy(Request $request, UserSession $session): JsonResponse
    {
        $this->authorize('delete', $session);

        $this->revokeSession->execute($session, $request->user());

        return ApiResponse::success(message: 'Sesi berhasil dicabut.');
    }
}
