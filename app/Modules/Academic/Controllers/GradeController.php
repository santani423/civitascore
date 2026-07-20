<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Grade;
use Modules\Academic\Resources\GradeResource;

/** No `show`/detail route — a grade's detail is fully represented by its row. */
class GradeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Grade::class);

        $query = Grade::query()->with(['krsItem.student', 'krsItem.classSection.course', 'krsItem.academicTerm']);

        // student_id/class_section_id aren't columns on `grades` itself — they
        // live on the related krs_item, so they can't go through ListQuery's
        // generic `filterable` (which does a direct `where($column, ...)`).
        // Same reasoning as PaymentController's date_from/date_to.
        $filters = (array) $request->query('filter', []);

        if ($studentId = $filters['student_id'] ?? null) {
            $query->whereHas('krsItem', fn ($q) => $q->where('student_id', $studentId));
        }

        if ($classSectionId = $filters['class_section_id'] ?? null) {
            $query->whereHas('krsItem', fn ($q) => $q->where('class_section_id', $classSectionId));
        }

        $paginator = ListQuery::paginate(
            query: $query->latest(),
            request: $request,
            filterable: ['letter_grade'],
            sortable: ['created_at', 'score'],
        );

        return ApiResponse::paginated(GradeResource::collection($paginator));
    }
}
