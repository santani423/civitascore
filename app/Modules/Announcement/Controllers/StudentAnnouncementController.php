<?php

namespace Modules\Announcement\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Controllers\Concerns\ResolvesCurrentStudent;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Resources\AnnouncementResource;
use Modules\Announcement\Services\StudentAnnouncementFeed;

/**
 * Portal Mahasiswa — pengumuman yang ditujukan kepada mahasiswa yang login.
 */
class StudentAnnouncementController extends Controller
{
    use ResolvesCurrentStudent;

    public function __construct(private readonly StudentAnnouncementFeed $feed) {}

    public function index(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        $validated = $request->validate([
            'filter' => ['nullable', Rule::in(StudentAnnouncementFeed::FILTERS)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $paginator = $this->feed->paginate(
            $student,
            $request->user(),
            $validated['filter'] ?? 'all',
            $validated['search'] ?? null,
            (int) ($validated['per_page'] ?? 10),
        );

        $response = ApiResponse::paginated(AnnouncementResource::collection($paginator));
        $payload = $response->getData(true);
        $payload['meta']['unread_count'] = $this->feed->unreadCount($student, $request->user());

        return response()->json($payload);
    }

    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        $this->currentStudent($request);
        $this->authorize('viewAsStudent', $announcement);

        $announcement->loadExists(['reads as is_read' => fn ($query) => $query->where('user_id', $request->user()->id)]);

        return ApiResponse::success(new AnnouncementResource($announcement->load(['creator', 'attachment'])));
    }

    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        $student = $this->currentStudent($request);
        $this->authorize('viewAsStudent', $announcement);

        $this->feed->markRead($announcement, $request->user());

        return ApiResponse::success([
            'id' => $announcement->id,
            'is_read' => true,
            'unread_count' => $this->feed->unreadCount($student, $request->user()),
        ], 'Pengumuman ditandai sudah dibaca.');
    }
}
