<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HumanResource\Models\Position;
use Modules\HumanResource\Requests\UpsertPositionRequest;
use Modules\HumanResource\Resources\PositionResource;

/**
 * Master jabatan (menu Kepegawaian → Jabatan).
 */
class PositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Position::class);

        $paginator = ListQuery::paginate(
            query: Position::query()->withCount('employees')
                ->when(! $request->filled('sort'), fn ($query) => $query->orderBy('name')),
            request: $request,
            searchable: ['name', 'code'],
            filterable: ['type', 'is_active'],
            sortable: ['name', 'code'],
        );

        return ApiResponse::paginated(PositionResource::collection($paginator));
    }

    public function store(UpsertPositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);

        $position = Position::query()->create($request->validated());

        return ApiResponse::success(new PositionResource($position), 'Jabatan ditambahkan.', status: 201);
    }

    public function update(UpsertPositionRequest $request, Position $position): JsonResponse
    {
        $this->authorize('update', $position);

        $position->update($request->validated());

        return ApiResponse::success(new PositionResource($position), 'Jabatan diperbarui.');
    }

    public function destroy(Position $position): JsonResponse
    {
        $this->authorize('delete', $position);

        if ($position->employees()->exists()) {
            throw new ConflictException('Jabatan masih dipegang pegawai aktif. Nonaktifkan jabatan ini bila sudah tidak dipakai.');
        }

        $position->delete();

        return ApiResponse::success(null, 'Jabatan dihapus.');
    }
}
