<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\DocumentStatus;
use Modules\HumanResource\Models\EmployeeDocument;
use Modules\HumanResource\Requests\StoreDocumentRequest;
use Modules\HumanResource\Requests\UpdateDocumentRequest;
use Modules\HumanResource\Resources\EmployeeDocumentResource;
use Modules\HumanResource\Services\DocumentService;

class EmployeeDocumentController extends Controller
{
    private const RELATIONS = ['employee', 'file', 'verifier'];

    public function __construct(private readonly DocumentService $documents) {}

    /**
     * Default hanya versi berlaku; `?include_history=1` menampilkan semua
     * versi. `?expiring_within=30` → yang kedaluwarsa ≤ N hari (termasuk
     * yang sudah lewat).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $expiringWithin = $request->integer('expiring_within');

        $query = EmployeeDocument::query()
            ->with(self::RELATIONS)
            ->when(! $request->boolean('include_history'), fn ($builder) => $builder->where('is_current', true))
            ->when($expiringWithin > 0, fn ($builder) => $builder
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', CarbonImmutable::today()->addDays($expiringWithin)))
            ->when(! $request->filled('sort'), fn ($builder) => $builder->latest());

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            searchable: ['title', 'document_number'],
            filterable: ['employee_id', 'document_type', 'status'],
            sortable: ['created_at', 'expires_at', 'document_type'],
        );

        return ApiResponse::paginated(EmployeeDocumentResource::collection($paginator));
    }

    public function versions(EmployeeDocument $document): JsonResponse
    {
        $this->authorize('view', $document);

        $chain = collect([$document]);
        $cursor = $document;

        while ($cursor->previous_version_id !== null && $chain->count() < 50) {
            $cursor = EmployeeDocument::query()->withTrashed()->find($cursor->previous_version_id);

            if ($cursor === null) {
                break;
            }

            $chain->push($cursor);
        }

        return ApiResponse::success(EmployeeDocumentResource::collection($chain->each->load(self::RELATIONS)));
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeDocument::class);
        $data = $request->validated();
        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();

        $replaces = null;
        if (! empty($data['replaces_document_id'])) {
            $replaces = EmployeeDocument::query()->whereKey($data['replaces_document_id'])->firstOrFail();

            if ($replaces->employee_id !== $employee->id || ! $replaces->is_current) {
                throw ValidationException::withMessages(['replaces_document_id' => 'Dokumen yang diganti tidak valid untuk pegawai ini.']);
            }
        }

        unset($data['employee_id'], $data['replaces_document_id']);
        $document = $this->documents->upload($employee, $data, $this->actor($request), $replaces);

        return ApiResponse::success(new EmployeeDocumentResource($document->load(self::RELATIONS)), 'Dokumen berhasil diunggah.', status: 201);
    }

    public function update(UpdateDocumentRequest $request, EmployeeDocument $document): JsonResponse
    {
        $this->authorize('update', $document);

        $document = $this->documents->updateMetadata($document, $request->validated());

        return ApiResponse::success(new EmployeeDocumentResource($document->load(self::RELATIONS)), 'Dokumen diperbarui.');
    }

    public function verify(Request $request, EmployeeDocument $document): JsonResponse
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'status' => ['required', Rule::enum(DocumentStatus::class)->only([DocumentStatus::Valid, DocumentStatus::Invalid])],
            'verification_note' => ['nullable', 'required_if:status,invalid', 'string', 'max:1000'],
        ]);

        $document = $this->documents->verify($document, DocumentStatus::from($data['status']), $data['verification_note'] ?? null, $this->actor($request));

        return ApiResponse::success(new EmployeeDocumentResource($document->load(self::RELATIONS)), 'Status dokumen diperbarui.');
    }

    public function destroy(EmployeeDocument $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $this->documents->delete($document);

        return ApiResponse::success(null, 'Dokumen dihapus.');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
