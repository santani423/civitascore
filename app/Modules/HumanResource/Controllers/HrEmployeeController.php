<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Requests\StoreHrEmployeeRequest;
use Modules\HumanResource\Requests\UpdateHrEmployeeRequest;
use Modules\HumanResource\Resources\HrEmployeeDetailResource;
use Modules\HumanResource\Resources\HrEmployeeResource;
use Modules\HumanResource\Services\EmployeeService;
use Modules\HumanResource\Services\HrExportService;
use Modules\HumanResource\Services\HrReportService;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Models\UserUniversity;
use Symfony\Component\HttpFoundation\Response;

class HrEmployeeController extends Controller
{
    private const DETAIL_RELATIONS = ['lecturer', 'faculty', 'studyProgram', 'workUnit', 'rank', 'user', 'supervisor'];

    public function __construct(
        private readonly EmployeeService $employees,
        private readonly HrReportService $reports,
        private readonly HrExportService $exports,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('hr.employees.viewAny');

        return $this->list($request, null);
    }

    public function lecturers(Request $request): JsonResponse
    {
        $this->authorize('hr.employees.viewAny', EmployeeType::Lecturer);

        return $this->list($request, EmployeeType::Lecturer);
    }

    public function staff(Request $request): JsonResponse
    {
        $this->authorize('hr.employees.viewAny', EmployeeType::Staff);

        return $this->list($request, EmployeeType::Staff);
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('hr.employees.view', $employee);

        return ApiResponse::success(new HrEmployeeDetailResource($employee->load(self::DETAIL_RELATIONS)));
    }

    public function store(StoreHrEmployeeRequest $request): JsonResponse
    {
        $type = EmployeeType::from((string) $request->validated('employee_type'));
        $this->authorize('hr.employees.create', $type);

        ['employee' => $employee, 'account' => $account] = $this->employees->create($request->validated(), $this->actor($request));

        // Kredensial awal dosen dikembalikan sekali ini saja supaya SDM bisa
        // menyerahkannya ke dosen — sama seperti POST /lecturers.
        $message = match (true) {
            $account === null => 'Pegawai berhasil ditambahkan.',
            $account['created'] => 'Dosen dan akun login berhasil ditambahkan.',
            default => 'Dosen berhasil ditambahkan dan ditautkan ke akun login yang sudah ada.',
        };

        return ApiResponse::success(
            new HrEmployeeDetailResource($employee->load(self::DETAIL_RELATIONS)),
            $message,
            meta: $account === null ? [] : ['credentials' => [
                'email' => $account['user']->email,
                'password' => $account['password'],
                'account_created' => $account['created'],
            ]],
            status: 201,
        );
    }

    public function update(UpdateHrEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('hr.employees.update', $employee);

        $employee = $this->employees->update($employee, $request->validated());

        return ApiResponse::success(new HrEmployeeDetailResource($employee->load(self::DETAIL_RELATIONS)), 'Data pegawai berhasil diperbarui.');
    }

    public function deactivate(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('hr.employees.update', $employee);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'inactive_at' => ['nullable', 'date'],
        ]);

        $employee = $this->employees->deactivate($employee, $data['reason'], $data['inactive_at'] ?? null);

        return ApiResponse::success(new HrEmployeeDetailResource($employee->load(self::DETAIL_RELATIONS)), 'Pegawai dinonaktifkan.');
    }

    public function activate(Employee $employee): JsonResponse
    {
        $this->authorize('hr.employees.update', $employee);

        $employee = $this->employees->activate($employee);

        return ApiResponse::success(new HrEmployeeDetailResource($employee->load(self::DETAIL_RELATIONS)), 'Pegawai diaktifkan kembali.');
    }

    /**
     * Nilai lengkap data sensitif (NIK, NPWP, rekening). Terpisah dari
     * detail supaya membuka data sensitif adalah tindakan eksplisit — dan
     * setiap aksesnya tercatat di audit log (siapa, kapan, data siapa).
     */
    public function sensitive(Request $request, Employee $employee, AuditLogService $audit): JsonResponse
    {
        $this->authorize('hr.employees.view', $employee);
        abort_unless($request->user()?->hasPermissionTo('hr_sensitive.read'), 403, 'Anda tidak memiliki izin melihat data sensitif pegawai.');

        $audit->recordAction($employee, AuditAction::ViewedSensitive, ['fields' => HrEmployeeDetailResource::SENSITIVE_FIELDS]);

        return ApiResponse::success([
            'nik' => $employee->nik,
            'npwp' => $employee->npwp,
            'bank_account_number' => $employee->bank_account_number,
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('hr.employees.delete', $employee);

        $this->employees->delete($employee);

        return ApiResponse::success(null, 'Data pegawai berhasil dihapus.');
    }

    /**
     * Export daftar pegawai sesuai filter yang sedang aktif di layar.
     */
    public function export(Request $request): Response
    {
        $this->authorize('hr.employees.export');

        $data = $request->validate([
            'format' => ['required', 'in:xlsx,pdf'],
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'array'],
        ]);

        $type = $data['filter']['employee_type'] ?? null;
        $reportType = match ($type) {
            EmployeeType::Lecturer->value => 'lecturers',
            EmployeeType::Staff->value => 'staff',
            default => 'employees',
        };

        $report = $this->reports->build($reportType, [...($data['filter'] ?? []), 'search' => $data['search'] ?? null]);

        return $this->exports->download($data['format'], HrReportService::REPORT_TYPES[$reportType], $report);
    }

    /**
     * Jumlah pegawai per status kepegawaian (halaman Status Kepegawaian).
     */
    public function statusSummary(): JsonResponse
    {
        $this->authorize('hr.employees.viewAny');

        $counts = Employee::query()
            ->selectRaw('employment_status, employee_type, count(*) as total')
            ->groupBy('employment_status', 'employee_type')
            ->get();

        $rows = collect(EmploymentStatus::cases())->map(fn (EmploymentStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
            'lecturers' => (int) $counts->first(fn ($row) => $row->getRawOriginal('employment_status') === $status->value && $row->getRawOriginal('employee_type') === EmployeeType::Lecturer->value)?->getAttribute('total'),
            'staff' => (int) $counts->first(fn ($row) => $row->getRawOriginal('employment_status') === $status->value && $row->getRawOriginal('employee_type') === EmployeeType::Staff->value)?->getAttribute('total'),
        ])->map(fn (array $row): array => [...$row, 'total' => $row['lecturers'] + $row['staff']])->values();

        return ApiResponse::success($rows);
    }

    /**
     * Akun pengguna di universitas ini yang belum tertaut ke pegawai mana
     * pun — pilihan untuk "tautkan akun login" di form pegawai.
     */
    public function userOptions(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermissionTo('hr_employees.create') || $request->user()?->hasPermissionTo('hr_employees.update'),
            403,
        );

        $search = trim((string) $request->query('search', ''));
        $memberIds = UserUniversity::query()
            ->where('university_id', app(TenantContext::class)->universityId())
            ->where('status', MembershipStatus::Active)
            ->pluck('user_id');
        $linkedIds = Employee::query()->withTrashed()->whereNotNull('user_id')->pluck('user_id');

        $users = User::query()
            ->whereIn('id', $memberIds)
            ->whereNotIn('id', $linkedIds)
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return ApiResponse::success($users);
    }

    private function list(Request $request, ?EmployeeType $type): JsonResponse
    {
        $query = Employee::query()
            ->with(['lecturer', 'faculty', 'studyProgram'])
            ->when($type !== null, fn ($builder) => $builder->where('employee_type', $type))
            // Urutan default hanya kalau klien tidak minta sort — ListQuery
            // menambahkan orderBy di belakang, jadi orderBy di sini akan
            // selalu menang.
            ->when(! $request->filled('sort'), fn ($builder) => $builder->orderBy('name'));

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            searchable: ['name', 'nip', 'email'],
            filterable: [
                'employee_type', 'employment_status', 'work_unit_id', 'position_id', 'faculty_id', 'study_program_id',
                'highest_education', 'staff_category', 'is_active', 'gender',
            ],
            sortable: ['name', 'nip', 'joined_at', 'created_at', 'unit_kerja'],
        );

        return ApiResponse::paginated(HrEmployeeResource::collection($paginator));
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
