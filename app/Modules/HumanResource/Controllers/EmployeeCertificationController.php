<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Models\EmployeeCertification;
use Modules\HumanResource\Requests\UpsertCertificationRequest;
use Modules\HumanResource\Resources\EmployeeCertificationResource;
use Modules\HumanResource\Services\Concerns\SavesRecordWithFiles;

class EmployeeCertificationController extends Controller
{
    use SavesRecordWithFiles;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeeCertification::class);

        $expiringWithin = $request->integer('expiring_within');

        $paginator = ListQuery::paginate(
            query: EmployeeCertification::query()->with(['employee', 'documentFile'])
                ->when($expiringWithin > 0, fn ($query) => $query
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<=', CarbonImmutable::today()->addDays($expiringWithin)))
                ->when(! $request->filled('sort'), fn ($query) => $query->latest('issued_at')),
            request: $request,
            searchable: ['name', 'issuer', 'certificate_number'],
            filterable: ['employee_id'],
            sortable: ['issued_at', 'expires_at', 'name'],
        );

        return ApiResponse::paginated(EmployeeCertificationResource::collection($paginator));
    }

    public function store(UpsertCertificationRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeCertification::class);
        $data = $request->validated();
        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();

        $certification = $this->saveWithFiles(
            new EmployeeCertification(['employee_id' => $employee->id, 'university_id' => $employee->university_id]),
            $data,
            ['document_file_id'],
            $this->actor($request),
        );

        return ApiResponse::success(new EmployeeCertificationResource($certification->load(['employee', 'documentFile'])), 'Sertifikasi berhasil ditambahkan.', status: 201);
    }

    public function update(UpsertCertificationRequest $request, EmployeeCertification $certification): JsonResponse
    {
        $this->authorize('update', $certification);

        $certification = $this->saveWithFiles($certification, $request->validated(), ['document_file_id'], $this->actor($request));

        return ApiResponse::success(new EmployeeCertificationResource($certification->load(['employee', 'documentFile'])), 'Sertifikasi berhasil diperbarui.');
    }

    public function destroy(EmployeeCertification $certification): JsonResponse
    {
        $this->authorize('delete', $certification);

        $certification->delete();

        return ApiResponse::success(null, 'Sertifikasi dihapus.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
