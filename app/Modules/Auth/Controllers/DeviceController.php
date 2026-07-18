<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\UserDevice;
use Modules\Auth\Resources\UserDeviceResource;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UserDevice::class);

        return ApiResponse::success(UserDeviceResource::collection($request->user()->devices));
    }
}
