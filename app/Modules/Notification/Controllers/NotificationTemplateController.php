<?php

namespace Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Requests\UpsertNotificationTemplateRequest;
use Modules\Notification\Resources\NotificationTemplateResource;

class NotificationTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NotificationTemplate::class);

        $paginator = ListQuery::paginate(
            query: NotificationTemplate::query(),
            request: $request,
            searchable: ['event_key', 'name'],
            filterable: ['channel', 'is_active'],
            sortable: ['event_key', 'created_at'],
        );

        return ApiResponse::paginated(NotificationTemplateResource::collection($paginator));
    }

    public function store(UpsertNotificationTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', NotificationTemplate::class);

        $template = NotificationTemplate::create($request->validated());

        return ApiResponse::success(new NotificationTemplateResource($template), 'Template berhasil dibuat.', status: 201);
    }

    public function update(UpsertNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): JsonResponse
    {
        $this->authorize('update', NotificationTemplate::class);

        $notificationTemplate->update($request->validated());

        return ApiResponse::success(new NotificationTemplateResource($notificationTemplate), 'Template berhasil diperbarui.');
    }
}
