<?php

namespace Modules\HumanResource\Support;

use Modules\Academic\Models\Employee;
use Modules\ApprovalWorkflow\Contracts\ContextualApproverResolver;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Models\WorkUnit;

/**
 * Approver kontekstual berbasis data kepegawaian:
 *  - position          → pegawai aktif yang memegang jabatan approver_position_id,
 *  - direct_supervisor → atasan langsung pemohon (employees.supervisor_employee_id),
 *  - unit_head         → kepala unit kerja pemohon; bila pemohon sendiri kepala
 *                        unitnya, naik ke kepala unit induk.
 *
 * "Pemohon" = pegawai subjek pengajuan (HrRequest::employee — bisa berbeda
 * dengan yang mengirim, mis. SDM mengajukan atas nama pegawai), atau
 * pegawai yang tertaut ke akun pengirim untuk pengajuan non-SDM.
 *
 * Hanya pegawai aktif yang punya akun login yang bisa menjadi approver
 * (R12: pegawai nonaktif tidak muncul di pemilihan approver). Seluruh
 * query ter-scope tenant lewat TenantScoped.
 */
class HrApproverResolver implements ContextualApproverResolver
{
    private const MAX_UNIT_DEPTH = 20;

    public function resolveUserIds(ApprovalWorkflowStep $step, ApprovalRequest $request): array
    {
        return match ($step->approver_type) {
            ApprovalApproverType::Position => $this->positionHolders($step->approver_position_id),
            ApprovalApproverType::DirectSupervisor => $this->directSupervisor($this->subject($request)),
            ApprovalApproverType::UnitHead => $this->unitHead($this->subject($request)),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function positionHolders(?string $positionId): array
    {
        if ($positionId === null) {
            return [];
        }

        return Employee::query()
            ->where('position_id', $positionId)
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id): string => (string) $id)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function directSupervisor(?Employee $employee): array
    {
        $supervisor = $employee?->supervisor_employee_id !== null
            ? Employee::query()->whereKey($employee->supervisor_employee_id)->where('is_active', true)->first()
            : null;

        return $supervisor?->user_id !== null ? [(string) $supervisor->user_id] : [];
    }

    /**
     * @return list<string>
     */
    private function unitHead(?Employee $employee): array
    {
        $unitId = $employee?->work_unit_id;

        for ($depth = 0; $unitId !== null && $depth < self::MAX_UNIT_DEPTH; $depth++) {
            $unit = WorkUnit::query()->whereKey($unitId)->first();

            if ($unit === null) {
                return [];
            }

            $head = $unit->head_employee_id !== null
                ? Employee::query()->whereKey($unit->head_employee_id)->where('is_active', true)->first()
                : null;

            if ($head !== null && $head->id !== $employee?->id && $head->user_id !== null) {
                return [(string) $head->user_id];
            }

            $unitId = $unit->parent_id;
        }

        return [];
    }

    private function subject(ApprovalRequest $request): ?Employee
    {
        if ($request->requestable_type === (new HrRequest)->getMorphClass()) {
            $hrRequest = HrRequest::query()->whereKey($request->requestable_id)->first();

            if ($hrRequest !== null) {
                return Employee::query()->whereKey($hrRequest->employee_id)->first();
            }
        }

        return Employee::query()->where('user_id', $request->requested_by)->first();
    }
}
