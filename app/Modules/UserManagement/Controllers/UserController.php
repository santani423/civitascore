<?php

namespace Modules\UserManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UserManagement\Resources\UserSummaryResource;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $paginator = ListQuery::paginate(
            query: User::query(),
            request: $request,
            searchable: ['name', 'email'],
            filterable: ['is_active'],
            sortable: ['name', 'created_at'],
        );

        return ApiResponse::paginated(UserSummaryResource::collection($paginator));
    }
}
