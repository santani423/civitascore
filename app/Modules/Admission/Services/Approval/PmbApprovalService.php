<?php

namespace Modules\Admission\Services\Approval;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Services\ApprovalRequestService;
use Modules\UserManagement\Models\Role;

/**
 * Pengajuan ke approval engine yang sudah ada (KD-05) untuk alur PMB
 * (10 §6 seed `approval_workflows`). Bila tenant belum mengatur alurnya,
 * dibuat alur default (pola StudentRequestService) — dapat diubah lewat
 * halaman Alur Persetujuan.
 */
class PmbApprovalService
{
    public const RESULT_APPROVAL = 'pmb_result_approval';

    public const RESULT_CORRECTION = 'pmb_result_correction';

    public const SCORE_CORRECTION = 'pmb_score_correction';

    public const REFUND = 'pmb_refund';

    public const BULK_EXTENSION = 'pmb_bulk_extension';

    public const PUBLICATION_ACCELERATION = 'pmb_publication_acceleration';

    /**
     * Alur default per jenis: [nama, [[nama langkah, role slug], ...]].
     * Pengesahan hasil dua langkah: Ketua Panitia → Pejabat Pengesah (Q-ANN-01 default).
     */
    private const DEFAULTS = [
        self::RESULT_APPROVAL => ['Pengesahan Hasil Seleksi PMB', [['Ketua Panitia PMB', 'admission_administrator'], ['Pejabat Pengesah', 'admission_approver']]],
        self::RESULT_CORRECTION => ['Koreksi Hasil / Pembatalan Penerimaan PMB', [['Pejabat Pengesah', 'admission_approver']]],
        self::SCORE_CORRECTION => ['Koreksi Nilai Terkunci PMB', [['Pejabat Pengesah', 'admission_approver']]],
        self::REFUND => ['Pengembalian Dana PMB', [['Kepala Keuangan', 'finance_administrator']]],
        self::BULK_EXTENSION => ['Perpanjangan Massal Daftar Ulang PMB', [['Pejabat Pengesah', 'admission_approver']]],
        self::PUBLICATION_ACCELERATION => ['Percepatan Publikasi Hasil PMB', [['Pejabat Pengesah', 'admission_approver']]],
    ];

    public function __construct(private readonly ApprovalRequestService $requests) {}

    /**
     * @param  array<string, mixed>  $context  kondisi pemilihan alur (mis. path_category)
     */
    public function submit(Model $requestable, string $type, User $requester, ?string $notes = null, array $context = []): ApprovalRequest
    {
        return $this->requests->submit($requestable, $this->workflow($type, $context), $requester, $notes);
    }

    /** @param  array<string, mixed>  $context */
    public function workflow(string $type, array $context = []): ApprovalWorkflow
    {
        $workflow = ApprovalWorkflow::query()->where('workflowable_type', $type)->where('is_active', true)->get()
            ->filter(function (ApprovalWorkflow $workflow) use ($context): bool {
                foreach ((array) ($workflow->conditions ?? []) as $key => $value) {
                    if (($context[$key] ?? null) !== $value) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn (ApprovalWorkflow $w) => count($w->conditions ?? []))
            ->first();

        return ($workflow ?? $this->createDefault($type))->load('steps');
    }

    public function createDefault(string $type): ApprovalWorkflow
    {
        [$name, $steps] = self::DEFAULTS[$type] ?? throw new ConflictException("Alur persetujuan {$type} tidak dikenal.");

        // university_id eksplisit: dipanggil juga dari seeder (WithoutModelEvents
        // mematikan pengisian otomatis TenantScoped).
        $workflow = ApprovalWorkflow::query()->firstOrCreate(
            ['workflowable_type' => $type, 'name' => $name],
            ['university_id' => app(TenantContext::class)->universityId(), 'description' => 'Alur default modul PMB (dapat diubah di Alur Persetujuan).', 'is_active' => true],
        );

        if ($workflow->steps()->doesntExist()) {
            foreach ($steps as $index => [$stepName, $roleSlug]) {
                $role = Role::query()->where('slug', $roleSlug)->whereNull('university_id')->first()
                    ?? throw new ConflictException("Role {$roleSlug} belum tersedia. Jalankan seeder role terlebih dahulu.");

                $workflow->steps()->create([
                    'sequence' => $index + 1,
                    'name' => $stepName,
                    'approver_type' => ApprovalApproverType::Role,
                    'approver_role_id' => $role->getKey(),
                ]);
            }
        }

        return $workflow;
    }

    /** @return array<int, string> */
    public static function types(): array
    {
        return array_keys(self::DEFAULTS);
    }
}
