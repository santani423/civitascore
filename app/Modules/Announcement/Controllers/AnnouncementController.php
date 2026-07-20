<?php

namespace Modules\Announcement\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Resources\AnnouncementResource;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Announcement::class);

        $paginator = ListQuery::paginate(
            query: Announcement::query()->with('creator')->orderByDesc('is_pinned')->orderByDesc('published_at'),
            request: $request,
            searchable: ['title'],
            filterable: ['target_scope', 'is_pinned'],
            sortable: ['published_at'],
        );

        return ApiResponse::paginated(AnnouncementResource::collection($paginator));
    }

    public function show(Announcement $announcement): JsonResponse
    {
        $this->authorize('view', $announcement);

        return ApiResponse::success(new AnnouncementResource($announcement->load('creator')));
    }
}
