<?php

namespace Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Resources\InvoiceResource;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $paginator = ListQuery::paginate(
            query: Invoice::query()->with('student')->latest('due_date'),
            request: $request,
            searchable: ['period'],
            filterable: ['status', 'student_id'],
            sortable: ['due_date', 'amount'],
        );

        return ApiResponse::paginated(InvoiceResource::collection($paginator));
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        return ApiResponse::success(new InvoiceResource($invoice->load(['student', 'payments'])));
    }
}
