<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Enums\InstallmentRequestStatus;
use Modules\Finance\Enums\InstallmentStatus;
use Modules\Finance\Models\InstallmentPlan;
use Modules\Finance\Models\InstallmentRequest;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\Payment;
use Modules\Finance\Support\Approvals;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;

/**
 * Cicilan (F-07): pengajuan sesuai skema (disetujui otomatis bila standar)
 * atau khusus (perlu persetujuan), jadwal termin, penjadwalan ulang
 * berversi, pembatalan jadwal. Σ termin = sisa tagihan (BR-KEU-31).
 */
class InstallmentService
{
    public function __construct(
        private readonly InvoiceLedger $ledger,
        private readonly KeuSettings $settings,
        private readonly FinanceNotifier $notifier,
    ) {}

    /**
     * @param  array<int, array{percent: float|int|string, due_date?: ?string, due_offset_days?: ?int}>|null  $customTerms
     */
    public function request(Invoice $invoice, ?InstallmentPlan $plan, ?array $customTerms, ?string $reason, User $actor, string $kind = 'new'): InstallmentRequest
    {
        if (! $invoice->status->isOpen() || Money::toCents($invoice->outstanding()) <= 0) {
            throw new ConflictException('Tagihan ini tidak memiliki sisa kewajiban untuk dicicil.');
        }

        if (InstallmentRequest::query()->where('invoice_id', $invoice->id)->where('status', InstallmentRequestStatus::Submitted)->exists()) {
            throw new ConflictException('Masih ada pengajuan cicilan yang menunggu keputusan untuk tagihan ini.');
        }

        $hasSchedule = InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')->exists();

        if ($kind === 'new' && $hasSchedule) {
            throw new ConflictException('Tagihan ini sudah memiliki jadwal cicilan aktif. Ajukan penjadwalan ulang bila perlu.');
        }

        if ($kind === 'reschedule' && ! $hasSchedule) {
            throw new ConflictException('Tagihan ini belum memiliki jadwal cicilan.');
        }

        if ($plan !== null) {
            if (! $plan->is_active) {
                throw new ConflictException('Skema cicilan ini tidak aktif.');
            }

            if (! in_array($invoice->bill_group->value, $plan->bill_groups, true)) {
                throw new ConflictException('Skema cicilan ini tidak berlaku untuk jenis tagihan tersebut.');
            }

            if ($plan->require_no_arrears && $this->hasOtherArrears($invoice)) {
                throw new ConflictException('Cicilan tidak dapat diajukan karena masih ada tunggakan pada tagihan lain (BR-KEU-30).');
            }
        }

        $terms = $customTerms ?? $plan?->terms ?? [];
        $this->validateTerms($terms);

        $request = InstallmentRequest::query()->create([
            'student_id' => $invoice->student_id,
            'invoice_id' => $invoice->id,
            'installment_plan_id' => $plan?->id,
            'terms' => array_values($terms),
            'kind' => $kind,
            'status' => InstallmentRequestStatus::Submitted,
            'reason' => $reason,
            'requested_by' => $actor->id,
        ]);

        if ($plan !== null && $plan->auto_approve && $customTerms === null && $kind === 'new') {
            return $this->approve($request, null, 'Disetujui otomatis sesuai skema standar.');
        }

        return $request;
    }

    public function approve(InstallmentRequest $request, ?User $actor, ?string $note = null): InstallmentRequest
    {
        if ($request->status !== InstallmentRequestStatus::Submitted) {
            throw new ConflictException('Pengajuan cicilan ini sudah diputuskan.');
        }

        if ($actor !== null) {
            Approvals::assertDistinct($actor, [$request->requested_by]);
        }

        DB::transaction(function () use ($request, $actor, $note): void {
            $invoice = $this->ledger->lock($request->invoice_id);
            $version = (int) InvoiceInstallment::query()->where('invoice_id', $invoice->id)->max('schedule_version') + 1;

            InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')
                ->update(['superseded_at' => now(), 'status' => InstallmentStatus::Superseded->value]);
            InstallmentRequest::query()->where('invoice_id', $invoice->id)->where('plan_status', 'active')->update(['plan_status' => 'superseded']);

            $this->buildSchedule($invoice, $request, $version);
            $request->forceFill([
                'status' => InstallmentRequestStatus::Approved,
                'plan_status' => 'active',
                'schedule_version' => $version,
                'decided_by' => $actor?->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ])->save();

            $this->ledger->recalculate($invoice);
        });

        $this->notify($request, 'disetujui');

        return $request->fresh();
    }

    public function reject(InstallmentRequest $request, User $actor, string $note): InstallmentRequest
    {
        if ($request->status !== InstallmentRequestStatus::Submitted) {
            throw new ConflictException('Pengajuan cicilan ini sudah diputuskan.');
        }

        Approvals::assertDistinct($actor, [$request->requested_by]);
        $request->forceFill(['status' => InstallmentRequestStatus::Rejected, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => $note])->save();
        $this->notify($request, 'ditolak: '.$note);

        return $request;
    }

    /** Membatalkan jadwal aktif: tagihan kembali ke jatuh tempo asalnya. */
    public function cancelSchedule(Invoice $invoice, string $reason, User $actor): void
    {
        DB::transaction(function () use ($invoice, $reason, $actor): void {
            $locked = $this->ledger->lock($invoice->id);
            InvoiceInstallment::query()->where('invoice_id', $locked->id)->whereNull('superseded_at')
                ->update(['superseded_at' => now(), 'status' => InstallmentStatus::Superseded->value]);
            InstallmentRequest::query()->where('invoice_id', $locked->id)->where('plan_status', 'active')
                ->update(['plan_status' => 'cancelled', 'decision_note' => $reason, 'decided_by' => $actor->id]);
            $this->ledger->recalculate($locked);
        });
    }

    private function buildSchedule(Invoice $invoice, InstallmentRequest $request, int $version): void
    {
        $paidOutside = (int) Payment::query()->where('invoice_id', $invoice->id)->where('status', Payment::POSTED)->get(['amount'])
            ->sum(fn (Payment $p) => Money::toCents($p->amount));
        $total = Money::toCents($invoice->amount) - $paidOutside;
        $unit = max(1, $this->settings->int('keu.rounding.unit')) * 100;
        $base = CarbonImmutable::parse($invoice->issued_at ?? $invoice->created_at)->startOfDay();
        $terms = array_values($request->terms);
        $allocated = 0;

        foreach ($terms as $index => $term) {
            $isLast = $index === count($terms) - 1;
            $cents = $isLast ? $total - $allocated : Money::percentCents($total, (float) $term['percent'], $unit);
            $allocated += $cents;
            $due = ! empty($term['due_date']) ? CarbonImmutable::parse($term['due_date']) : $base->addDays((int) ($term['due_offset_days'] ?? 30 * ($index + 1)));

            InvoiceInstallment::query()->create([
                'university_id' => $invoice->university_id,
                'invoice_id' => $invoice->id,
                'installment_request_id' => $request->id,
                'seq' => $index + 1,
                'schedule_version' => $version,
                'amount' => Money::fromCents($cents),
                'paid_amount' => 0,
                'due_date' => $due->toDateString(),
                'status' => InstallmentStatus::Scheduled,
            ]);
        }
    }

    /** @param  array<int, mixed>  $terms */
    private function validateTerms(array $terms): void
    {
        if (count($terms) < 2 || count($terms) > 12) {
            throw new ConflictException('Cicilan terdiri atas 2 sampai 12 termin.');
        }

        $sum = 0.0;
        $previous = null;

        foreach ($terms as $term) {
            $percent = (float) ($term['percent'] ?? 0);

            if ($percent <= 0) {
                throw new ConflictException('Persentase setiap termin harus lebih dari nol.');
            }

            $sum += $percent;
            $due = $term['due_date'] ?? null;

            if ($due !== null && $previous !== null && $due <= $previous) {
                throw new ConflictException('Tanggal jatuh tempo termin harus berurutan.');
            }

            $previous = $due ?? $previous;
        }

        if (abs($sum - 100) > 0.001) {
            throw new ConflictException('Jumlah persentase termin harus 100%.');
        }
    }

    private function hasOtherArrears(Invoice $invoice): bool
    {
        return Invoice::query()->where('student_id', $invoice->student_id)->where('id', '!=', $invoice->id)->where('is_overdue', true)->exists();
    }

    private function notify(InstallmentRequest $request, string $status): void
    {
        $request->loadMissing(['student', 'invoice']);
        $this->notifier->student($request->student, 'keu.installment_decided', ['number' => (string) $request->invoice->invoice_number, 'status' => $status]);
    }
}
