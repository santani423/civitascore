<?php

namespace Modules\Finance\Console;

use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Academic\Services\AcademicBlockService;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Services\ClearanceService;
use Modules\Finance\Services\FinanceNotifier;
use Modules\Finance\Services\InvoiceLedger;
use Modules\Finance\Services\OnlinePaymentService;
use Modules\Finance\Services\PenaltyService;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;
use Modules\Tenancy\Models\University;
use Throwable;

/**
 * Pekerjaan harian KEU per kampus: penanda tunggakan, denda berjalan,
 * transaksi & dispensasi kedaluwarsa, pengingat jatuh tempo, dan evaluasi
 * cekal keuangan (jaring pengaman bila event terlewat).
 */
class KeuDailyCommand extends Command
{
    protected $signature = 'keu:daily {--university= : kode universitas}';

    protected $description = 'Pekerjaan harian Modul KEU (tunggakan, denda, kedaluwarsa, pengingat, cekal).';

    public function handle(TenantContext $tenant): int
    {
        $universities = University::query()->when($this->option('university'), fn ($q, $code) => $q->where('code', $code))->get();

        foreach ($universities as $university) {
            $tenant->setUniversityId($university->id);

            try {
                $this->runFor();
            } catch (Throwable $e) {
                report($e);
                $this->error("Gagal untuk {$university->code}: {$e->getMessage()}");
            }
        }

        $tenant->setUniversityId(null);

        return self::SUCCESS;
    }

    private function runFor(): void
    {
        $ledger = app(InvoiceLedger::class);
        $today = CarbonImmutable::today();

        Invoice::query()->whereIn('status', InvoiceStatus::open())->chunkById(300, function ($invoices) use ($ledger): void {
            foreach ($invoices as $invoice) {
                $before = [$invoice->is_overdue, $invoice->overdue_since?->toDateString()];
                $ledger->refreshOverdue($invoice);

                if ($before !== [$invoice->is_overdue, $invoice->overdue_since?->toDateString()]) {
                    $invoice->save();
                }
            }
        });

        $penalties = app(PenaltyService::class)->accrueAll($today);
        $expired = app(OnlinePaymentService::class)->expireStale();
        $exemptions = app(AcademicBlockService::class)->expireExemptions();
        $reminders = $this->reminders($today);
        $evaluated = app(ClearanceService::class)->evaluateAll($today);

        $this->line("denda={$penalties} transaksi_kedaluwarsa={$expired} dispensasi_berakhir={$exemptions} pengingat={$reminders} cekal_dievaluasi={$evaluated}");
    }

    private function reminders(CarbonImmutable $today): int
    {
        $offsets = array_map('intval', (array) app(KeuSettings::class)->get('keu.reminder.offsets_days'));
        $dates = array_map(fn ($o) => $today->subDays($o)->toDateString(), $offsets);
        $notifier = app(FinanceNotifier::class);
        $count = 0;

        Invoice::query()->whereIn('status', InvoiceStatus::open())->whereIn('due_date', $dates)->with('student')->chunkById(300, function ($invoices) use ($notifier, &$count): void {
            foreach ($invoices as $invoice) {
                $notifier->student($invoice->student, 'keu.due_reminder', [
                    'number' => (string) $invoice->invoice_number, 'amount' => Money::format($invoice->outstanding()), 'due_date' => $invoice->due_date->format('d-m-Y'),
                ]);
                $count++;
            }
        });

        return $count;
    }
}
