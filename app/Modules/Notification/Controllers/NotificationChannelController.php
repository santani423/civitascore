<?php

namespace Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Notification\Models\NotificationChannelConfig;
use Modules\Notification\Requests\UpdateNotificationChannelRequest;
use Modules\Notification\Resources\NotificationChannelConfigResource;

class NotificationChannelController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', NotificationChannelConfig::class);

        return ApiResponse::success(NotificationChannelConfigResource::collection(NotificationChannelConfig::all()));
    }

    public function update(UpdateNotificationChannelRequest $request, NotificationChannelConfig $notificationChannel): JsonResponse
    {
        $this->authorize('update', NotificationChannelConfig::class);

        $notificationChannel->update($request->validated());

        return ApiResponse::success(new NotificationChannelConfigResource($notificationChannel), 'Channel notifikasi berhasil diperbarui.');
    }
}
