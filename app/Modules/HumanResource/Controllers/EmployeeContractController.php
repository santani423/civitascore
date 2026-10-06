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
use Modules\HumanResource\Enums\ContractStatus;
use Modules\HumanResource\Models\EmployeeContract;
use Modules\HumanResource\Requests\UpsertContractRequest;
use Modules\HumanResource\Resources\EmployeeContractResource;
use Modules\HumanResource\Services\ContractService;

class EmployeeContractController extends Controller
{
    public function __construct(private readonly ContractService $contracts) {}

    /**
     * `?expiring_within=90` → hanya kontrak aktif yang berakhir dalam N
     * hari ke depan (dipakai alert dashboard & filter "akan berakhir").
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeeContract::class);

        $expiringWithin = $request->integer('expiring_within');

        $query = EmployeeContract::query()
            ->with(['employee', 'documentFile'])
            ->when($expiringWithin > 0, fn ($builder) => $builder
                ->where('status', ContractStatus::Active)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', CarbonImmutable::today())
                ->whereDate('end_date', '<=', CarbonImmutable::today()->addDays($expiringWithin)))
            ->when(! $request->filled('sort'), fn ($builder) => $builder->orderByRaw('end_date is null')->orderBy('end_date'));

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            searchable: ['contract_number'],
            filterable: ['employee_id', 'status', 'contract_type'],
            sortable: ['start_date', 'end_date', 'contract_number'],
        );

        return ApiResponse::paginated(EmployeeContractResource::collection($paginator));
    }

    public function store(UpsertContractRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeContract::class);
        $data = $request->validated();
        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();
        unset($data['employee_id']);

        $contract = $this->contracts->save($employee, $data, $this->actor($request));

        return ApiResponse::success(new EmployeeContractResource($contract->load(['employee', 'documentFile'])), 'Kontrak berhasil ditambahkan.', status: 201);
    }

    public function update(UpsertContractRequest $request, EmployeeContract $contract): JsonResponse
    {
        $this->authorize('update', $contract);
        $employee = $contract->employee;
        abort_if($employee === null, 404);

        $contract = $this->contracts->save($employee, $request->validated(), $this->actor($request), $contract);

        return ApiResponse::success(new EmployeeContractResource($contract->load(['employee', 'documentFile'])), 'Kontrak berhasil diperbarui.');
    }

    public function terminate(Request $request, EmployeeContract $contract): JsonResponse
    {
        $this->authorize('update', $contract);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);

        $contract = $this->contracts->terminate($contract, $data['notes'] ?? null);

        return ApiResponse::success(new EmployeeContractResource($contract->load(['employee', 'documentFile'])), 'Kontrak diakhiri.');
    }

    public function destroy(EmployeeContract $contract): JsonResponse
    {
        $this->authorize('delete', $contract);

        $contract->delete();

        return ApiResponse::success(null, 'Kontrak dihapus.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
