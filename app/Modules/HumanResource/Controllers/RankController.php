<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HumanResource\Models\Rank;
use Modules\HumanResource\Requests\UpsertRankRequest;
use Modules\HumanResource\Resources\RankResource;

/**
 * Master pangkat/golongan (menu Kepegawaian → Kepangkatan).
 */
class RankController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Rank::class);

        $paginator = ListQuery::paginate(
            query: Rank::query()->withCount('employees')
                ->when(! $request->filled('sort'), fn ($query) => $query->orderBy('level')),
            request: $request,
            searchable: ['name', 'grade'],
            filterable: ['is_active'],
            sortable: ['level', 'grade', 'name'],
        );

        return ApiResponse::paginated(RankResource::collection($paginator));
    }

    public function store(UpsertRankRequest $request): JsonResponse
    {
        $this->authorize('create', Rank::class);

        $rank = Rank::query()->create($request->validated());

        return ApiResponse::success(new RankResource($rank), 'Pangkat ditambahkan.', status: 201);
    }

    public function update(UpsertRankRequest $request, Rank $rank): JsonResponse
    {
        $this->authorize('update', $rank);

        $rank->update($request->validated());

        return ApiResponse::success(new RankResource($rank), 'Pangkat diperbarui.');
    }

    public function destroy(Rank $rank): JsonResponse
    {
        $this->authorize('delete', $rank);

        if ($rank->employees()->exists()) {
            throw new ConflictException('Pangkat masih dipakai pegawai. Nonaktifkan pangkat ini bila sudah tidak dipakai.');
        }

        $rank->delete();

        return ApiResponse::success(null, 'Pangkat dihapus.');
    }
}
