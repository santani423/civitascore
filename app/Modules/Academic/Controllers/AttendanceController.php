<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Requests\RecordAttendanceBatchRequest;
use Modules\Academic\Resources\AttendanceResource;
use Modules\Academic\Services\AcademicRecordService;

/** No `show`/detail route — an attendance entry's detail is fully represented by its row. */
class AttendanceController extends Controller
{
    public function __construct(private readonly AcademicRecordService $records) {}

    public function batchStore(RecordAttendanceBatchRequest $request, ClassSection $classSection): JsonResponse
    {
        $this->authorize('record', Attendance::class);

        $attendances = $this->records->recordAttendanceBatch($classSection, $request->validated());

        return ApiResponse::success(
            AttendanceResource::collection($attendances),
            'Kehadiran berhasil direkam.',
            status: 201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::query()->with(['krsItem.student', 'krsItem.classSection.course']);

        // student_id/class_section_id aren't columns on `attendances` itself —
        // they live on the related krs_item, so they can't go through
        // ListQuery's generic `filterable` (direct `where($column, ...)`).
        // Same reasoning as PaymentController's date_from/date_to.
        $filters = (array) $request->query('filter', []);

        if ($studentId = $filters['student_id'] ?? null) {
            $query->whereHas('krsItem', fn ($q) => $q->where('student_id', $studentId));
        }

        if ($classSectionId = $filters['class_section_id'] ?? null) {
            $query->whereHas('krsItem', fn ($q) => $q->where('class_section_id', $classSectionId));
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('meeting_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('meeting_date', '<=', $dateTo);
        }

        $paginator = ListQuery::paginate(
            query: $query->orderByDesc('meeting_date'),
            request: $request,
            filterable: ['status'],
            sortable: ['meeting_date', 'meeting_number'],
        );

        return ApiResponse::paginated(AttendanceResource::collection($paginator));
    }
}
