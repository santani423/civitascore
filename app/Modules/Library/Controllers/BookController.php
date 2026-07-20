<?php

namespace Modules\Library\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Library\Models\Book;
use Modules\Library\Resources\BookResource;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Book::class);

        $paginator = ListQuery::paginate(
            query: Book::query()->withCount('loans')->orderBy('title'),
            request: $request,
            searchable: ['title', 'author', 'isbn'],
            filterable: ['category', 'is_active'],
            sortable: ['title'],
        );

        return ApiResponse::paginated(BookResource::collection($paginator));
    }

    public function show(Book $book): JsonResponse
    {
        $this->authorize('view', $book);

        return ApiResponse::success(new BookResource(
            $book->load(['loans.student'])->loadCount('loans'),
        ));
    }
}
