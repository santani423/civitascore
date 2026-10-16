<?php

namespace Modules\Finance\Listeners;

use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Student;
use Modules\Finance\Events\StudentFinanceChanged;
use Modules\Finance\Services\ClearanceService;
use Throwable;

/**
 * Evaluasi ulang cekal keuangan segera setelah kewajiban mahasiswa berubah
 * (BR-KEU-66) — sinkron agar tidak bergantung pada worker antrean.
 * Kegagalan evaluasi tidak membatalkan transaksi keuangan (BR-KEU-68).
 */
class ReevaluateFinancialClearance
{
    public function __construct(private readonly ClearanceService $clearance, private readonly TenantContext $tenant) {}

    public function handle(StudentFinanceChanged $event): void
    {
        $previous = $this->tenant->universityId();

        try {
            $this->tenant->setUniversityId($event->universityId);
            $student = Student::query()->find($event->studentId);

            if ($student !== null) {
                $this->clearance->evaluateStudent($student);
            }
        } catch (Throwable $e) {
            report($e);
        } finally {
            $this->tenant->setUniversityId($previous);
        }
    }
}
