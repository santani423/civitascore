<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Lecturer;
use Modules\AuditLog\Models\AuditLog;
use Modules\HumanResource\Models\EmployeeCertification;
use Modules\HumanResource\Models\EmployeeContract;
use Modules\HumanResource\Models\EmployeeDocument;
use Modules\HumanResource\Models\EmployeeEducation;
use Modules\HumanResource\Models\EmployeePosition;
use Modules\HumanResource\Models\EmployeeRank;
use Modules\HumanResource\Models\EmployeeTraining;
use Modules\HumanResource\Models\EmployeeTransfer;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Models\LeaveRequest;
use Modules\HumanResource\Models\LecturerAcademicRank;
use Modules\HumanResource\Models\LecturerActivity;
use Modules\HumanResource\Models\PerformanceReview;
use Modules\HumanResource\Models\Position;
use Modules\HumanResource\Models\Rank;
use Modules\HumanResource\Models\WorkUnit;

/**
 * Audit Log SDM — tampilan audit_logs (Modul AuditLog yang sudah ada)
 * yang dibatasi ke model-model kepegawaian. Isinya ditulis otomatis oleh
 * trait Auditable: user, aksi, record, nilai lama/baru, IP, waktu.
 */
class HrAuditLogController extends Controller
{
    /** @var array<class-string, string> */
    public const MODULES = [
        Employee::class => 'Data Pegawai',
        Lecturer::class => 'Data Dosen',
        EmployeeEducation::class => 'Riwayat Pendidikan',
        EmployeePosition::class => 'Riwayat Jabatan',
        EmployeeRank::class => 'Kepangkatan',
        LecturerAcademicRank::class => 'Jabatan Akademik',
        LecturerActivity::class => 'Penelitian & Pengabdian',
        EmployeeContract::class => 'Kontrak Kerja',
        EmployeeDocument::class => 'Dokumen Kepegawaian',
        EmployeeTraining::class => 'Pelatihan',
        EmployeeCertification::class => 'Sertifikasi',
        PerformanceReview::class => 'Kinerja Pegawai',
        EmployeeTransfer::class => 'Penempatan & Mutasi',
        LeaveRequest::class => 'Cuti & Izin',
        HrRequest::class => 'Pengajuan SDM',
        WorkUnit::class => 'Unit Kerja',
        Position::class => 'Master Jabatan',
        Rank::class => 'Master Pangkat',
    ];

    /** Model riwayat yang punya kolom employee_id. */
    private const EMPLOYEE_OWNED = [
        EmployeeEducation::class, EmployeePosition::class, EmployeeRank::class, LecturerAcademicRank::class,
        LecturerActivity::class, EmployeeContract::class, EmployeeDocument::class, EmployeeTraining::class,
        EmployeeCertification::class, PerformanceReview::class, EmployeeTransfer::class, LeaveRequest::class,
        HrRequest::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()
            ->with('user')
            ->whereIn('auditable_type', array_keys(self::MODULES))
            ->when($request->filled('module'), function (Builder $builder) use ($request): void {
                $class = array_search((string) $request->query('module'), $this->moduleKeys(), true);
                $builder->where('auditable_type', $class === false ? '__none__' : $class);
            })
            ->when($request->filled('date_from'), fn (Builder $builder) => $builder->whereDate('created_at', '>=', (string) $request->query('date_from')))
            ->when($request->filled('date_to'), fn (Builder $builder) => $builder->whereDate('created_at', '<=', (string) $request->query('date_to')))
            ->latest('created_at');

        return $this->paginate($request, $query);
    }

    /**
     * Riwayat perubahan satu pegawai lintas seluruh tabel SDM-nya.
     */
    public function forEmployee(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('hr.employees.view', $employee);

        $query = AuditLog::query()
            ->with('user')
            ->where(function (Builder $builder) use ($employee): void {
                $builder->where(fn (Builder $inner) => $inner->where('auditable_type', Employee::class)->where('auditable_id', $employee->id));

                if ($employee->lecturer !== null) {
                    $builder->orWhere(fn (Builder $inner) => $inner->where('auditable_type', Lecturer::class)->where('auditable_id', $employee->lecturer->id));
                }

                foreach (self::EMPLOYEE_OWNED as $class) {
                    $ids = $class::query()->where('employee_id', $employee->id)->pluck('id');

                    if ($ids->isNotEmpty()) {
                        $builder->orWhere(fn (Builder $inner) => $inner->where('auditable_type', $class)->whereIn('auditable_id', $ids));
                    }
                }
            })
            ->latest('created_at');

        return $this->paginate($request, $query);
    }

    public function modules(): JsonResponse
    {
        return ApiResponse::success(collect($this->moduleKeys())->map(fn (string $key, string $class): array => [
            'value' => $key,
            'label' => self::MODULES[$class],
        ])->values());
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    private function paginate(Request $request, Builder $query): JsonResponse
    {
        $paginator = ListQuery::paginate(query: $query, request: $request, filterable: ['action', 'user_id']);
        $keys = $this->moduleKeys();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil.',
            'data' => collect($paginator->items())->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'user_name' => $log->user->name ?? 'Sistem',
                'action' => $log->action->value,
                'module' => $keys[$log->auditable_type] ?? null,
                'module_label' => self::MODULES[$log->auditable_type] ?? $log->auditable_type,
                'record_id' => $log->auditable_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Kunci modul yang aman diekspos ke klien (bukan nama kelas PHP).
     *
     * @return array<string, string> nama kelas => kunci modul
     */
    private function moduleKeys(): array
    {
        return collect(self::MODULES)->mapWithKeys(fn (string $label, string $class): array => [
            $class => Str::snake(class_basename($class)),
        ])->all();
    }
}
