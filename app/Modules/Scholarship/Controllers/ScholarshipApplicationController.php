<?php

namespace Modules\Scholarship\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Scholarship\Models\ScholarshipApplication;
use Modules\Scholarship\Resources\ScholarshipApplicationResource;

/**
 * No `show`/detail route — an application's detail is fully represented by
 * its row, and is otherwise shown embedded on its scholarship's detail page
 * (see ScholarshipController::show()). This index exists so a specific
 * student's applications can be listed directly (e.g. from Student Detail)
 * without walking every scholarship — gated by the same `scholarships.read`
 * permission as the parent resource, same reasoning as PaymentController
 * reusing invoices.read.
 */
class ScholarshipApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ScholarshipApplication::class);

        $paginator = ListQuery::paginate(
            query: ScholarshipApplication::query()->with(['scholarship', 'student'])->latest('submitted_at'),
            request: $request,
            filterable: ['student_id', 'scholarship_id', 'status'],
            sortable: ['submitted_at'],
        );

        return ApiResponse::paginated(ScholarshipApplicationResource::collection($paginator));
    }
}
