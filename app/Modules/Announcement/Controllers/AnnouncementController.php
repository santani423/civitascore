<?php

namespace Modules\Announcement\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\Announcement\Enums\AnnouncementTargetScope;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Requests\UpsertAnnouncementRequest;
use Modules\Announcement\Resources\AnnouncementResource;
use Modules\FileManagement\Support\FileAttacher;

class AnnouncementController extends Controller
{
    public function __construct(private readonly FileAttacher $files) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Announcement::class);

        $paginator = ListQuery::paginate(
            query: Announcement::query()->with(['creator', 'attachment'])->orderByDesc('is_pinned')->orderByDesc('published_at'),
            request: $request,
            searchable: ['title'],
            filterable: ['target_scope', 'is_pinned', 'audience'],
            sortable: ['published_at'],
        );

        return ApiResponse::paginated(AnnouncementResource::collection($paginator));
    }

    public function show(Announcement $announcement): JsonResponse
    {
        $this->authorize('view', $announcement);

        return ApiResponse::success(new AnnouncementResource($announcement->load(['creator', 'attachment'])));
    }

    public function store(UpsertAnnouncementRequest $request): JsonResponse
    {
        $this->authorize('create', Announcement::class);

        $announcement = $this->save(new Announcement(['created_by' => $request->user()->id]), $request);

        return ApiResponse::success(new AnnouncementResource($announcement), 'Pengumuman diterbitkan.', status: 201);
    }

    public function update(UpsertAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        $this->authorize('update', $announcement);

        return ApiResponse::success(new AnnouncementResource($this->save($announcement, $request)), 'Pengumuman diperbarui.');
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return ApiResponse::success(null, 'Pengumuman dihapus.');
    }

    private function save(Announcement $announcement, UpsertAnnouncementRequest $request): Announcement
    {
        $data = $request->validated();
        $scope = AnnouncementTargetScope::from((string) ($data['target_scope'] ?? $announcement->target_scope?->value ?? 'universitas'));
        $targetId = $scope === AnnouncementTargetScope::Universitas ? null : ($data['target_id'] ?? $announcement->target_id);

        // Target harus fakultas/prodi milik universitas ini (query ter-scope tenant).
        $targetExists = match ($scope) {
            AnnouncementTargetScope::Universitas => true,
            AnnouncementTargetScope::Fakultas => Faculty::query()->whereKey($targetId)->exists(),
            AnnouncementTargetScope::ProgramStudi => StudyProgram::query()->whereKey($targetId)->exists(),
        };

        if (! $targetExists) {
            throw ValidationException::withMessages(['target_id' => 'Sasaran pengumuman tidak ditemukan.']);
        }

        $attachment = array_key_exists('attachment_file_id', $data)
            ? $this->files->resolve($data['attachment_file_id'], 'attachment_file_id', $request->user(), $announcement->exists ? $announcement : null)
            : $announcement->attachment;

        return DB::transaction(function () use ($announcement, $data, $scope, $targetId, $attachment): Announcement {
            $announcement->fill([
                ...Arr::only($data, ['title', 'body', 'audience', 'target_admission_year', 'target_semester', 'is_pinned']),
                'target_scope' => $scope,
                'target_id' => $targetId,
                'attachment_file_id' => $attachment?->id,
                'published_at' => $data['published_at'] ?? $announcement->published_at ?? now(),
            ]);
            $announcement->audience ??= 'all';
            $announcement->save();

            $this->files->attach($attachment, $announcement);

            return $announcement->load(['creator', 'attachment']);
        });
    }
}
