<?php

namespace Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notification\Models\UserNotificationPreference;
use Modules\Notification\Requests\UpsertNotificationPreferenceRequest;
use Modules\Notification\Resources\UserNotificationPreferenceResource;

class UserNotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $preferences = UserNotificationPreference::query()->where('user_id', $request->user()->id)->get();

        return ApiResponse::success(UserNotificationPreferenceResource::collection($preferences));
    }

    public function store(UpsertNotificationPreferenceRequest $request): JsonResponse
    {
        $preference = UserNotificationPreference::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'channel' => $request->validated('channel'),
                'notification_type' => $request->validated('notification_type', '*'),
            ],
            ['is_enabled' => $request->validated('is_enabled')],
        );

        return ApiResponse::success(new UserNotificationPreferenceResource($preference), 'Preferensi notifikasi berhasil disimpan.');
    }
}
