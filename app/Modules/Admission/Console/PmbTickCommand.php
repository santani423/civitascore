<?php

namespace Modules\Admission\Console;

use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Modules\Admission\Outbox\OutboxProcessor;
use Modules\Admission\Services\Announcement\PublicationService;
use Modules\Admission\Services\Configuration\PeriodService;
use Modules\Admission\Services\Conversion\ConversionService;
use Modules\Admission\Services\Exam\CbtService;
use Modules\Admission\Services\Payment\PaymentService;
use Modules\Admission\Services\Payment\WebhookService;
use Modules\Admission\Services\Verification\VerificationService;
use Modules\Tenancy\Models\University;
use Throwable;

/**
 * Job tiap menit: publikasi terjadwal (FR-ANN-03), outbox, konversi,
 * callback tertunda, polling transaksi (F.4 aturan 6), kedaluwarsa transaksi,
 * klaim verifikasi kedaluwarsa, status gelombang, auto-submit CBT.
 */
class PmbTickCommand extends Command
{
    protected $signature = 'pmb:tick';

    protected $description = 'Menjalankan pekerjaan berkala PMB (tiap menit).';

    public function handle(TenantContext $tenant): int
    {
        $steps = [
            'publikasi' => fn () => app(PublicationService::class)->publishDue(),
            'outbox' => fn () => app(OutboxProcessor::class)->processPending(200),
            'konversi' => fn () => app(ConversionService::class)->processPending(50),
            'callback' => fn () => app(WebhookService::class)->retryPending(),
            'polling' => fn () => app(PaymentService::class)->pollPending(),
            'kedaluwarsa transaksi' => fn () => app(PaymentService::class)->expireOverdueTransactions(),
        ];

        foreach ($steps as $label => $step) {
            $this->runStep($label, $step);
        }

        foreach (University::query()->where('is_active', true)->pluck('id') as $universityId) {
            $tenant->setUniversityId((string) $universityId);

            $this->runStep("klaim verifikasi [{$universityId}]", fn () => app(VerificationService::class)->expireClaims());
            $this->runStep("status gelombang [{$universityId}]", fn () => app(PeriodService::class)->refreshWaveStatuses());
            $this->runStep("cbt [{$universityId}]", fn () => app(CbtService::class)->autoSubmitExpired());
        }

        $tenant->setUniversityId(null);

        return self::SUCCESS;
    }

    private function runStep(string $label, callable $step): void
    {
        try {
            $result = $step();
            $this->line("{$label}: ".(is_scalar($result) ? $result : json_encode($result)));
        } catch (Throwable $e) {
            report($e);
            $this->error("{$label}: {$e->getMessage()}");
        }
    }
}
