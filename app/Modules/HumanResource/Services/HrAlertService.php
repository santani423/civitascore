<?php

namespace Modules\HumanResource\Services;

use Carbon\CarbonImmutable;
use Modules\HumanResource\Enums\ContractStatus;
use Modules\HumanResource\Enums\DocumentStatus;
use Modules\HumanResource\Enums\HrRequestStatus;
use Modules\HumanResource\Enums\HrRequestType;
use Modules\HumanResource\Models\EmployeeCertification;
use Modules\HumanResource\Models\EmployeeContract;
use Modules\HumanResource\Models\EmployeeDocument;
use Modules\HumanResource\Models\HrRequest;

/**
 * Informasi penting SDM yang butuh tindakan — dipakai bersama oleh
 * Dashboard SDM (ringkas) dan halaman Notifikasi SDM (lengkap). Selalu
 * dihitung langsung dari data, bukan dari tabel notifikasi, jadi tidak
 * pernah basi.
 */
class HrAlertService
{
    public const CONTRACT_WINDOW_DAYS = 90;

    public const DOCUMENT_WINDOW_DAYS = 30;

    /**
     * @return array<string, array{count: int, items: array<int, array<string, mixed>>}>
     */
    public function all(int $limit = 10): array
    {
        return [
            'contracts_expiring' => $this->contractsExpiring($limit),
            'documents_expiring' => $this->documentsExpiring($limit),
            'pending_requests' => $this->pendingRequests($limit),
            'requests_to_process' => $this->requestsToProcess($limit),
        ];
    }

    /**
     * @return array{count: int, items: array<int, array<string, mixed>>}
     */
    public function contractsExpiring(int $limit): array
    {
        $today = CarbonImmutable::today();
        $query = EmployeeContract::query()
            ->where('status', ContractStatus::Active)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $today->addDays(self::CONTRACT_WINDOW_DAYS));

        return [
            'count' => (clone $query)->count(),
            'items' => $query->with('employee:id,name,nip')->orderBy('end_date')->limit($limit)->get()
                ->map(fn (EmployeeContract $contract): array => [
                    'id' => $contract->id,
                    'employee_id' => $contract->employee_id,
                    'employee_name' => $contract->employee?->name,
                    'contract_number' => $contract->contract_number,
                    'end_date' => $contract->end_date?->toDateString(),
                    'days_remaining' => $contract->daysRemaining(),
                ])->all(),
        ];
    }

    /**
     * Dokumen kepegawaian & sertifikasi yang kedaluwarsa dalam 30 hari ke
     * depan atau sudah lewat.
     *
     * @return array{count: int, items: array<int, array<string, mixed>>}
     */
    public function documentsExpiring(int $limit): array
    {
        $limitDate = CarbonImmutable::today()->addDays(self::DOCUMENT_WINDOW_DAYS);

        $documents = EmployeeDocument::query()
            ->where('is_current', true)
            ->where('status', '!=', DocumentStatus::Invalid)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $limitDate);

        $certifications = EmployeeCertification::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $limitDate);

        $items = $documents->clone()->with('employee:id,name')->orderBy('expires_at')->limit($limit)->get()
            ->map(fn (EmployeeDocument $document): array => [
                'id' => $document->id,
                'source' => 'document',
                'employee_id' => $document->employee_id,
                'employee_name' => $document->employee?->name,
                'name' => $document->title ?? $document->document_type->label(),
                'expires_at' => $document->expires_at?->toDateString(),
                'is_expired' => $document->isExpired(),
            ])
            ->concat($certifications->clone()->with('employee:id,name')->orderBy('expires_at')->limit($limit)->get()
                ->map(fn (EmployeeCertification $certification): array => [
                    'id' => $certification->id,
                    'source' => 'certification',
                    'employee_id' => $certification->employee_id,
                    'employee_name' => $certification->employee?->name,
                    'name' => $certification->name,
                    'expires_at' => $certification->expires_at?->toDateString(),
                    'is_expired' => $certification->expires_at?->isPast() ?? false,
                ]))
            ->sortBy('expires_at')
            ->take($limit)
            ->values()
            ->all();

        return ['count' => $documents->count() + $certifications->count(), 'items' => $items];
    }

    /**
     * @return array{count: int, items: array<int, array<string, mixed>>}
     */
    public function pendingRequests(int $limit): array
    {
        $query = HrRequest::query()->where('status', HrRequestStatus::Pending);

        return [
            'count' => (clone $query)->count(),
            'items' => $query->with('employee:id,name')->latest()->limit($limit)->get()
                ->map(fn (HrRequest $request): array => $this->requestItem($request))->all(),
        ];
    }

    /**
     * Mutasi/kenaikan jabatan/dokumen yang sudah disetujui tapi belum
     * ditindaklanjuti SDM.
     *
     * @return array{count: int, items: array<int, array<string, mixed>>}
     */
    public function requestsToProcess(int $limit): array
    {
        $query = HrRequest::query()
            ->where('status', HrRequestStatus::Approved)
            ->whereIn('type', [HrRequestType::Transfer, HrRequestType::Promotion, HrRequestType::Document])
            ->whereNull('processed_at');

        return [
            'count' => (clone $query)->count(),
            'items' => $query->with('employee:id,name')->orderBy('approved_at')->limit($limit)->get()
                ->map(fn (HrRequest $request): array => $this->requestItem($request))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function requestItem(HrRequest $request): array
    {
        return [
            'id' => $request->id,
            'employee_id' => $request->employee_id,
            'employee_name' => $request->employee?->name,
            'type' => $request->type->value,
            'type_label' => $request->type->label(),
            'title' => $request->title,
            'created_at' => $request->created_at?->toIso8601String(),
        ];
    }
}
