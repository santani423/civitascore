<?php

namespace Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\Payment;
use Modules\Finance\Resources\PaymentResource;

/** No `show`/detail route — a payment's detail is shown inline on its invoice's detail page. */
class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::query()->with('invoice.student')->latest('paid_at');

        // date_from/date_to aren't generic ListQuery filterable columns
        // (range, not exact match) — same pattern as
        // DashboardStatsService::paymentTrend(), applied here so a
        // payment_trend chart bar click lands on exactly the same set.
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('paid_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('paid_at', '<=', $dateTo);
        }

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            filterable: ['invoice_id'],
            sortable: ['paid_at', 'amount'],
        );

        return ApiResponse::paginated(PaymentResource::collection($paginator));
    }
}
