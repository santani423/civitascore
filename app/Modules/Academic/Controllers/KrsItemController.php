<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Resources\KrsItemResource;

/** No `show`/detail route — a KRS entry's detail is fully represented by its row. */
class KrsItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KrsItem::class);

        $paginator = ListQuery::paginate(
            query: KrsItem::query()->with(['student', 'classSection.course', 'academicTerm', 'grade'])->latest(),
            request: $request,
            filterable: ['student_id', 'class_section_id', 'academic_term_id', 'status'],
            sortable: ['created_at'],
        );

        return ApiResponse::paginated(KrsItemResource::collection($paginator));
    }
}
