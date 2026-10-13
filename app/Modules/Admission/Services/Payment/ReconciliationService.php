<?php

namespace Modules\Admission\Services\Payment;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\ManualProofStatus;
use Modules\Admission\Enums\ReconciliationResolution;
use Modules\Admission\Enums\ReconciliationResult;
use Modules\Admission\Enums\TransactionStatus;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\ReconciliationBatch;
use Modules\Admission\Models\ReconciliationItem;
use Modules\Admission\Models\Refund;
use Modules\Admission\Payments\GatewayUnsupported;
use Modules\Admission\Payments\PaymentGatewayManager;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\Money;
use Modules\Admission\Support\PmbFileStorage;
use Modules\AuditLog\Enums\AuditAction;
use Throwable;

/**
 * Rekonsiliasi (05 §F.6, FR-PAY-09): laporan settlement gateway (bila
 * penyedia mendukung) atau impor mutasi bank CSV; hasil per item: matched,
 * missing_in_system (double-check & perbaiki otomatis), missing_in_provider,
 * amount_mismatch, duplicate_payment (kandidat refund otomatis).
 */
class ReconciliationService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentService $payments,
        private readonly PmbFileStorage $storage,
        private readonly HistoryRecorder $history,
    ) {}

    public function createBatch(string $provider, string $statementDate, ?User $actor): ReconciliationBatch
    {
        if (ReconciliationBatch::query()->where('provider', $provider)->whereDate('statement_date', $statementDate)->exists()) {
            throw new ConflictException('Batch rekonsiliasi untuk tanggal & penyedia ini sudah ada.', 'BATCH_EXISTS');
        }

        $batch = ReconciliationBatch::query()->create([
            'provider' => $provider,
            'statement_date' => $statementDate,
            'status' => 'open',
            'created_by' => $actor?->getKey(),
        ]);

        if ($provider !== 'bank_statement' && $this->gateways->has($provider)) {
            try {
                $rows = $this->gateways->gateway($provider)->fetchSettlement($statementDate);
                $this->matchSettlement($batch, $provider, $rows);
            } catch (GatewayUnsupported) {
                // Penyedia tanpa API settlement: lanjut dengan impor laporan/mutasi.
            }
        }

        $this->detectDuplicatePayments($batch, $statementDate);
        $this->refreshCounters($batch);

        return $batch->refresh();
    }

    /**
     * @param  array<int, array{reference: string, amount: string, paid_at: ?string, status: string}>  $rows
     */
    public function matchSettlement(ReconciliationBatch $batch, string $provider, array $rows): void
    {
        $seen = [];

        foreach ($rows as $row) {
            $transaction = PaymentTransaction::query()->where('provider', $provider)->where('provider_reference', $row['reference'])->first();
            $seen[] = $row['reference'];
            $result = match (true) {
                $transaction === null => ReconciliationResult::MissingInSystem,
                $transaction->status !== TransactionStatus::Success => ReconciliationResult::MissingInSystem,
                Money::compare($transaction->amount, $row['amount']) !== 0 => ReconciliationResult::AmountMismatch,
                default => ReconciliationResult::Matched,
            };

            $item = $this->addItem($batch, $transaction, $row['reference'], $row['amount'], $row['paid_at'], null, $result);

            // Kandidat callback hilang: double-check & perbaiki otomatis (F.6).
            if ($result === ReconciliationResult::MissingInSystem && $transaction?->status === TransactionStatus::Pending) {
                try {
                    $after = $this->payments->recheck($transaction, Actor::system());

                    if ($after->status === TransactionStatus::Success) {
                        $item->forceFill(['result' => ReconciliationResult::Matched, 'resolution' => ReconciliationResolution::AutoFixed, 'resolved_at' => now(), 'resolution_note' => 'Diperbaiki otomatis lewat double-check gateway.'])->save();
                    }
                } catch (Throwable) {
                }
            }
        }

        $date = CarbonImmutable::parse($batch->statement_date);
        PaymentTransaction::query()->where('provider', $provider)->where('status', TransactionStatus::Success->value)
            ->whereBetween('paid_at', [$date->startOfDay(), $date->endOfDay()])->whereNotIn('provider_reference', $seen)->get()
            ->each(fn (PaymentTransaction $t) => $this->addItem($batch, $t, $t->provider_reference, null, null, null, ReconciliationResult::MissingInProvider));
    }

    /**
     * Impor mutasi bank (CSV) untuk transfer manual: kolom dikenali dari
     * header (tanggal/date, keterangan/description, nominal/amount/kredit/credit,
     * referensi/reference). Saran pencocokan berdasarkan nominal unik + tanggal (F.5).
     */
    public function importBankStatement(ReconciliationBatch $batch, UploadedFile $file, User $actor): ReconciliationBatch
    {
        if ($batch->status === 'closed') {
            throw new ConflictException('Batch sudah ditutup.', 'BATCH_CLOSED');
        }

        $upload = $this->storage->store($file, ['text/csv', 'text/plain'], 5120, ['reconciliation', (string) $batch->getKey()], uploadedByUserId: (string) $actor->getKey());
        $rows = $this->parseCsv($this->storage->contents($upload));

        DB::transaction(function () use ($batch, $rows, $upload): void {
            $batch->forceFill(['source_file_id' => $upload->getKey()])->save();

            foreach ($rows as $row) {
                $candidate = $this->manualCandidate($row['amount'], $row['date']);
                $result = $candidate !== null ? ReconciliationResult::Matched : ReconciliationResult::MissingInSystem;
                $this->addItem($batch, $candidate, $row['reference'], $row['amount'], $row['date'], $row['description'], $result);
            }
        });

        $this->refreshCounters($batch);

        return $batch->refresh();
    }

    /** Selisih nominal callback (05 §F.4 aturan 5) → item rekonsiliasi hari ini. */
    public function flagMismatch(PaymentTransaction $transaction, string $externalAmount): void
    {
        $batch = ReconciliationBatch::query()->firstOrCreate(
            ['provider' => $transaction->provider, 'statement_date' => now()->toDateString()],
            ['status' => 'open'],
        );

        $this->addItem($batch, $transaction, $transaction->provider_reference, $externalAmount, now()->toDateString(), 'Nominal callback berbeda', ReconciliationResult::AmountMismatch);
        $this->refreshCounters($batch);
    }

    public function resolveItem(ReconciliationItem $item, string $resolution, string $note, User $actor): ReconciliationItem
    {
        $value = ReconciliationResolution::from($resolution);

        if ($item->batch->status === 'closed') {
            throw new ConflictException('Batch sudah ditutup.', 'BATCH_CLOSED');
        }

        $item->forceFill(['resolution' => $value, 'resolution_note' => $note, 'resolved_by' => $actor->getKey(), 'resolved_at' => now()])->save();
        $this->history->action($item, AuditAction::Updated, ['resolution' => $resolution], $note);
        $this->refreshCounters($item->batch);

        return $item->refresh();
    }

    public function closeBatch(ReconciliationBatch $batch, User $actor): ReconciliationBatch
    {
        $open = $batch->items()->where('result', '!=', ReconciliationResult::Matched->value)->whereNull('resolution')->count();

        if ($open > 0) {
            throw ValidationException::withMessages(['batch' => "Masih ada {$open} item yang belum diselesaikan."]);
        }

        $batch->forceFill(['status' => 'closed', 'closed_by' => $actor->getKey(), 'closed_at' => now()])->save();
        $this->history->action($batch, AuditAction::Updated, ['status' => 'closed']);

        return $batch;
    }

    /**
     * Job harian: batch settlement H-1 per penyedia gateway yang dipakai
     * tenant aktif + deteksi pembayaran ganda.
     */
    public function dailyRun(): int
    {
        $date = now()->subDay()->toDateString();
        $count = 0;
        $providers = PaymentTransaction::query()->whereNotIn('provider', PaymentGatewayManager::OFFLINE_PROVIDERS)->distinct()->pluck('provider');

        foreach ($providers as $provider) {
            if (! ReconciliationBatch::query()->where('provider', $provider)->whereDate('statement_date', $date)->exists()) {
                $this->createBatch((string) $provider, $date, null);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Dua transaksi sukses untuk tagihan yang sama melebihi nominal → item
     * duplicate_payment + draft refund DUPLICATE_PAYMENT untuk transaksi
     * kedua (F.7), tanpa pengajuan otomatis (keuangan meninjau dulu).
     */
    private function detectDuplicatePayments(ReconciliationBatch $batch, string $date): void
    {
        $invoiceIds = PaymentTransaction::query()->where('status', TransactionStatus::Success->value)
            ->whereDate('paid_at', '<=', $date)->select('invoice_id')->groupBy('invoice_id')->havingRaw('COUNT(*) > 1')->pluck('invoice_id');

        foreach (PmbInvoice::query()->whereIn('id', $invoiceIds)->get() as $invoice) {
            $successful = $invoice->transactions()->where('status', TransactionStatus::Success->value)->orderBy('paid_at')->get();
            $total = Money::add(...$successful->map(fn ($t) => (string) $t->amount)->all());

            if (Money::compare($total, (string) $invoice->amount_net) <= 0) {
                continue;
            }

            foreach ($successful->slice(1) as $transaction) {
                if (ReconciliationItem::query()->where('transaction_id', $transaction->getKey())->where('result', ReconciliationResult::DuplicatePayment->value)->exists()) {
                    continue;
                }

                $item = $this->addItem($batch, $transaction, $transaction->provider_reference, (string) $transaction->amount, optional($transaction->paid_at)->toDateString(), 'Pembayaran ganda', ReconciliationResult::DuplicatePayment);

                if (! Refund::query()->where('transaction_id', $transaction->getKey())->whereNotIn('status', ['rejected', 'failed'])->exists()) {
                    Refund::query()->create([
                        'university_id' => $transaction->university_id,
                        'transaction_id' => $transaction->getKey(),
                        'amount' => (string) $transaction->amount,
                        'reason_code' => 'DUPLICATE_PAYMENT',
                        'reason_note' => 'Dibuat otomatis oleh rekonsiliasi: pembayaran ganda untuk tagihan '.$invoice->invoice_number,
                        'beneficiary_bank' => '-',
                        'beneficiary_account' => '-',
                        'beneficiary_name' => $invoice->application->account->name,
                        'status' => 'draft',
                    ]);
                    $item->forceFill(['resolution' => ReconciliationResolution::RefundCreated])->save();
                }
            }
        }
    }

    private function addItem(ReconciliationBatch $batch, ?PaymentTransaction $transaction, ?string $reference, ?string $amount, ?string $date, ?string $description, ReconciliationResult $result): ReconciliationItem
    {
        return ReconciliationItem::query()->create([
            'university_id' => $batch->university_id,
            'batch_id' => $batch->getKey(),
            'transaction_id' => $transaction?->getKey(),
            'external_reference' => $reference,
            'external_amount' => $amount,
            'external_date' => $date ? CarbonImmutable::parse($date)->toDateString() : null,
            'external_description' => $description ? mb_substr($description, 0, 255) : null,
            'result' => $result,
        ]);
    }

    private function manualCandidate(string $amount, string $date): ?PaymentTransaction
    {
        $day = CarbonImmutable::parse($date);

        return PaymentTransaction::query()->where('provider', 'manual')->where('amount', Money::fromCents(Money::toCents($amount)))
            ->whereIn('status', [TransactionStatus::Pending->value, TransactionStatus::Success->value])
            ->whereBetween('created_at', [$day->subDays(3)->startOfDay(), $day->addDay()->endOfDay()])
            ->where(fn ($q) => $q->where('status', TransactionStatus::Success->value)->orWhereHas('manualProof', fn ($p) => $p->where('status', ManualProofStatus::Submitted->value)))
            ->orderBy('created_at')->first();
    }

    /**
     * @return array<int, array{date: string, description: ?string, amount: string, reference: ?string}>
     */
    private function parseCsv(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $lines = preg_split('/\R/', trim($contents)) ?: [];
        $delimiter = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';
        $header = array_map(fn ($h) => mb_strtolower(trim($h)), str_getcsv(array_shift($lines) ?? '', $delimiter));
        $find = fn (array $names) => collect($header)->search(fn ($h) => in_array($h, $names, true));
        $dateCol = $find(['tanggal', 'date', 'tgl']);
        $amountCol = $find(['nominal', 'amount', 'kredit', 'credit', 'jumlah']);
        $descCol = $find(['keterangan', 'description', 'deskripsi', 'uraian']);
        $refCol = $find(['referensi', 'reference', 'ref', 'no_referensi']);

        if ($dateCol === false || $amountCol === false) {
            throw ValidationException::withMessages(['file' => 'Kolom tanggal dan nominal tidak ditemukan pada CSV mutasi.']);
        }

        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line, $delimiter);
            $rawAmount = preg_replace('/[^\d,.\-]/', '', (string) ($cells[$amountCol] ?? '0')) ?? '0';
            // Format Indonesia "1.250.000,00" → "1250000.00".
            $normalized = str_contains($rawAmount, ',') && (strrpos($rawAmount, ',') > (strrpos($rawAmount, '.') ?: -1))
                ? str_replace(['.', ','], ['', '.'], $rawAmount)
                : str_replace(',', '', $rawAmount);

            if (Money::toCents($normalized) <= 0) {
                continue;
            }

            try {
                $date = CarbonImmutable::parse(str_replace('/', '-', (string) $cells[$dateCol]))->toDateString();
            } catch (Throwable) {
                continue;
            }

            $rows[] = [
                'date' => $date,
                'description' => $descCol !== false ? ($cells[$descCol] ?? null) : null,
                'amount' => Money::fromCents(Money::toCents($normalized)),
                'reference' => $refCol !== false ? ($cells[$refCol] ?? null) : null,
            ];
        }

        return $rows;
    }

    private function refreshCounters(ReconciliationBatch $batch): void
    {
        $batch->forceFill([
            'total_items' => $batch->items()->count(),
            'matched_items' => $batch->items()->where(fn ($q) => $q->where('result', ReconciliationResult::Matched->value)->orWhereNotNull('resolution'))->count(),
        ])->save();
    }
}
