<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\PerformanceCategory;
use Modules\HumanResource\Enums\PerformanceStatus;
use Modules\HumanResource\Models\PerformanceReview;
use Modules\HumanResource\Requests\UpsertPerformanceReviewRequest;
use Modules\HumanResource\Resources\PerformanceReviewResource;
use Modules\SystemSetting\Services\SystemSettingService;

/**
 * Penilaian kinerja. Kategori (Sangat Baik/Baik/Cukup/Perlu Perbaikan)
 * selalu diturunkan dari skor di backend — klien tidak mengirimnya —
 * memakai ambang dari system setting `hr.performance_thresholds` bila ada.
 */
class PerformanceReviewController extends Controller
{
    public function __construct(private readonly SystemSettingService $settings) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PerformanceReview::class);

        $paginator = ListQuery::paginate(
            query: PerformanceReview::query()->with(['employee', 'reviewer'])
                ->when(! $request->filled('sort'), fn ($query) => $query->latest()),
            request: $request,
            searchable: ['period'],
            filterable: ['employee_id', 'period', 'category', 'status'],
            sortable: ['score', 'period', 'created_at'],
        );

        return ApiResponse::paginated(PerformanceReviewResource::collection($paginator));
    }

    public function store(UpsertPerformanceReviewRequest $request): JsonResponse
    {
        $this->authorize('create', PerformanceReview::class);
        $data = $request->validated();
        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();
        $this->assertReviewer($data);

        $review = PerformanceReview::query()->create([
            ...$data,
            'university_id' => $employee->university_id,
            'category' => $this->categoryFor((float) $data['score']),
        ]);

        return ApiResponse::success(new PerformanceReviewResource($review->load(['employee', 'reviewer'])), 'Penilaian kinerja disimpan.', status: 201);
    }

    public function update(UpsertPerformanceReviewRequest $request, PerformanceReview $performanceReview): JsonResponse
    {
        $this->authorize('update', $performanceReview);

        if ($performanceReview->status === PerformanceStatus::Final) {
            throw new ConflictException('Penilaian yang sudah final tidak dapat diubah.');
        }

        $data = $request->validated();
        $this->assertReviewer($data);

        $performanceReview->update([...$data, 'category' => $this->categoryFor((float) $data['score'])]);

        return ApiResponse::success(new PerformanceReviewResource($performanceReview->load(['employee', 'reviewer'])), 'Penilaian kinerja diperbarui.');
    }

    public function destroy(PerformanceReview $performanceReview): JsonResponse
    {
        $this->authorize('delete', $performanceReview);

        if ($performanceReview->status === PerformanceStatus::Final) {
            throw new ConflictException('Penilaian yang sudah final tidak dapat dihapus.');
        }

        $performanceReview->delete();

        return ApiResponse::success(null, 'Penilaian kinerja dihapus.');
    }

    private function categoryFor(float $score): PerformanceCategory
    {
        $thresholds = $this->settings->get('hr.performance_thresholds', []);

        return PerformanceCategory::fromScore($score, is_array($thresholds) ? $thresholds : []);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertReviewer(array $data): void
    {
        if (! empty($data['reviewer_employee_id'])) {
            Employee::query()->whereKey($data['reviewer_employee_id'])->firstOrFail();
        }
    }
}
