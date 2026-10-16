<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\ReconciliationBatchStatus;
use Modules\Finance\Enums\ReconciliationResult;
use Modules\Finance\Enums\ReconciliationStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\CashierSession;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Models\ReconciliationBatch;
use Modules\Finance\Models\ReconciliationItem;
use Modules\Finance\Support\FinanceNumbering;
use Modules\Finance\Support\Money;

/**
 * Rekonsiliasi (F-12): impor mutasi bank / laporan settlement, pencocokan
 * berlapis (referensi → nominal+tanggal → setoran kas), resolusi
 * pengecualian, dan penutupan batch (BR-KEU-54..57). Dana tak
 * teridentifikasi tidak pernah masuk deposit mahasiswa sebelum diklaim.
 */
class ReconciliationService
{
    public const RESOLUTIONS = [
        'manual_match' => 'Dicocokkan manual dengan transaksi',
        'allocated_to_student' => 'Diklaim & dialokasikan ke mahasiswa',
        'returned_to_sender' => 'Dikembalikan ke pengirim',
        'escalated' => 'Dieskalasi ke bank/penyedia',
        'accepted_difference' => 'Selisih diterima dengan alasan',
        'ignored' => 'Diabaikan dengan alasan (bukan pembayaran mahasiswa)',
    ];

    public function __construct(
        private readonly PaymentPostingService $posting,
        private readonly FinanceNumbering $numbering,
        private readonly AuditLogService $audit,
    ) {}

    public function createBatch(string $source, string $statementDate, ?string $channelId, User $actor): ReconciliationBatch
    {
        return ReconciliationBatch::query()->create([
            'batch_number' => $this->numbering->next('reconciliation'),
            'source' => $source,
            'payment_channel_id' => $channelId,
            'statement_date' => $statementDate,
            'status' => ReconciliationBatchStatus::Open,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<int, array{date: string, description?: ?string, amount: string, reference?: ?string}>  $rows
     */
    public function import(ReconciliationBatch $batch, array $rows, bool $flagMissing = false): ReconciliationBatch
    {
        $this->assertOpen($batch);

        DB::transaction(function () use ($batch, $rows, $flagMissing): void {
            $matchedIds = [];

            foreach ($rows as $row) {
                $amount = Money::normalize($row['amount']);
                $reference = trim((string) ($row['reference'] ?? '')) ?: null;
                $date = CarbonImmutable::parse($row['date']);
                [$result, $tx] = $this->match($batch, $reference, $amount, $date, $row['description'] ?? null, $matchedIds);

                if ($tx !== null && $result === ReconciliationResult::Matched) {
                    $matchedIds[] = $tx->id;
                    $tx->forceFill(['reconciliation_status' => ReconciliationStatus::Matched, 'reconciled_at' => now()])->save();
                } elseif ($tx !== null) {
                    $tx->forceFill(['reconciliation_status' => ReconciliationStatus::Exception])->save();
                }

                ReconciliationItem::query()->create([
                    'batch_id' => $batch->id,
                    'payment_transaction_id' => $tx?->id,
                    'external_reference' => $reference,
                    'external_amount' => $amount,
                    'external_date' => $date->toDateString(),
                    'external_description' => isset($row['description']) ? mb_substr((string) $row['description'], 0, 255) : null,
                    'result' => $result,
                ]);
            }

            if ($flagMissing) {
                $this->flagMissingInStatement($batch, $matchedIds);
            }

            $this->refreshCounters($batch);
        });

        return $batch->fresh();
    }

    public function resolve(ReconciliationItem $item, string $resolution, string $note, User $actor, ?Student $student = null, ?string $transactionId = null): ReconciliationItem
    {
        $this->assertOpen($item->batch);

        if ($item->result === ReconciliationResult::Matched || $item->resolution !== null) {
            throw new ConflictException('Baris ini sudah cocok atau sudah diselesaikan.');
        }

        if (! isset(self::RESOLUTIONS[$resolution])) {
            throw new ConflictException('Jenis resolusi tidak dikenal.');
        }

        DB::transaction(function () use ($item, $resolution, $note, $actor, $student, $transactionId): void {
            if ($resolution === 'allocated_to_student') {
                if ($item->result !== ReconciliationResult::Unidentified || $student === null) {
                    throw new ConflictException('Alokasi ke mahasiswa hanya untuk dana tak teridentifikasi dan wajib memilih mahasiswa.');
                }

                $tx = PaymentTransaction::query()->create([
                    'university_id' => $student->university_id,
                    'transaction_number' => $this->numbering->next('transaction', null, $student->university_id),
                    'student_id' => $student->id,
                    'provider' => 'manual',
                    'method' => PaymentMethod::ManualTransfer,
                    'target_type' => PaymentTransaction::TARGET_DEPOSIT,
                    'amount' => (string) $item->external_amount,
                    'status' => TransactionStatus::AwaitingVerification,
                    'recorded_by' => $actor->id,
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ]);
                $this->posting->settle($tx, (string) $item->external_amount, CarbonImmutable::parse($item->external_date)->setTime(12, 0), $actor);
                $tx->refresh()->forceFill(['reconciliation_status' => ReconciliationStatus::Resolved, 'reconciled_at' => now()])->save();
                $item->payment_transaction_id = $tx->id;
            }

            if ($resolution === 'manual_match') {
                $tx = PaymentTransaction::query()->where('status', TransactionStatus::Success)->findOrFail($transactionId);
                $tx->forceFill(['reconciliation_status' => ReconciliationStatus::Resolved, 'reconciled_at' => now()])->save();
                $item->payment_transaction_id = $tx->id;
            }

            if (in_array($resolution, ['accepted_difference', 'escalated'], true) && $item->payment_transaction_id !== null) {
                PaymentTransaction::query()->whereKey($item->payment_transaction_id)->update(['reconciliation_status' => ReconciliationStatus::Resolved->value, 'reconciled_at' => now()]);
            }

            $item->forceFill(['resolution' => $resolution, 'resolution_note' => $note, 'resolved_by' => $actor->id, 'resolved_at' => now()])->save();
            $this->refreshCounters($item->batch);
        });

        $this->audit->recordAction($item->batch, AuditAction::Updated, ['item' => $item->id, 'resolution' => $resolution], $note);

        return $item->fresh();
    }

    public function close(ReconciliationBatch $batch, User $actor): ReconciliationBatch
    {
        $this->assertOpen($batch);
        $open = $batch->items()->where('result', '!=', ReconciliationResult::Matched->value)->whereNull('resolution')->count();

        if ($open > 0) {
            throw new ConflictException("Masih ada {$open} baris pengecualian yang belum diselesaikan (BR-KEU-55).", 'BATCH_HAS_OPEN_ITEMS');
        }

        $manualResolvers = $batch->items()->where('resolution', 'manual_match')->pluck('resolved_by')->filter()->unique()->all();

        if (in_array((string) $actor->id, $manualResolvers, true)) {
            throw new ConflictException('Petugas yang mencocokkan manual tidak dapat menutup batch yang sama (pemisahan tugas).', 'SELF_APPROVAL_FORBIDDEN');
        }

        $batch->forceFill(['status' => ReconciliationBatchStatus::Closed, 'closed_by' => $actor->id, 'closed_at' => now()])->save();
        $this->audit->recordAction($batch, AuditAction::Approved, ['action' => 'close']);

        return $batch;
    }

    /**
     * @param  array<int, string>  $alreadyMatched
     * @return array{0: ReconciliationResult, 1: ?PaymentTransaction}
     */
    private function match(ReconciliationBatch $batch, ?string $reference, string $amount, CarbonImmutable $date, ?string $description, array $alreadyMatched): array
    {
        if ($batch->source === 'cash_deposit') {
            $session = CashierSession::query()->where('deposit_reference', $reference)->first();

            if ($session === null) {
                return [ReconciliationResult::Unidentified, null];
            }

            $txs = PaymentTransaction::query()->where('cashier_session_id', $session->id)->where('status', TransactionStatus::Success)->get();
            $expected = $txs->sum(fn (PaymentTransaction $t) => Money::toCents($t->amount));
            $result = $expected === Money::toCents($amount) ? ReconciliationResult::Matched : ReconciliationResult::AmountMismatch;

            foreach ($txs as $tx) {
                $tx->forceFill(['reconciliation_status' => $result === ReconciliationResult::Matched ? ReconciliationStatus::Matched : ReconciliationStatus::Exception, 'reconciled_at' => now()])->save();
            }

            return [$result, null];
        }

        $tx = null;

        if ($reference !== null) {
            $tx = PaymentTransaction::query()->where(fn ($q) => $q->where('provider_reference', $reference)->orWhere('transaction_number', $reference)->orWhere('receipt_number', $reference))->first();
        }

        if ($tx === null) {
            // Nominal dibandingkan dalam sen (kolom decimal tidak dibandingkan sebagai string).
            $tx = PaymentTransaction::query()->where('status', TransactionStatus::Success)
                ->whereIn('method', [PaymentMethod::ManualTransfer, PaymentMethod::Online])
                ->whereBetween('paid_at', [$date->subDay()->startOfDay(), $date->addDay()->endOfDay()])
                ->whereNotIn('id', $alreadyMatched)
                ->where('reconciliation_status', ReconciliationStatus::Unreconciled)
                ->orderBy('paid_at')->get()
                ->first(fn (PaymentTransaction $t) => Money::compare($t->amount, $amount) === 0);
        }

        if ($tx === null) {
            return [ReconciliationResult::Unidentified, null];
        }

        if (in_array($tx->id, $alreadyMatched, true) || $tx->reconciliation_status === ReconciliationStatus::Matched) {
            return [ReconciliationResult::Duplicate, $tx];
        }

        if ($tx->status !== TransactionStatus::Success) {
            return [ReconciliationResult::MissingInSystem, $tx];
        }

        return [Money::compare($tx->amount, $amount) === 0 ? ReconciliationResult::Matched : ReconciliationResult::AmountMismatch, $tx];
    }

    /** @param  array<int, string>  $matchedIds */
    private function flagMissingInStatement(ReconciliationBatch $batch, array $matchedIds): void
    {
        $date = CarbonImmutable::parse($batch->statement_date);

        PaymentTransaction::query()->where('status', TransactionStatus::Success)
            ->when($batch->payment_channel_id, fn ($q) => $q->where('payment_channel_id', $batch->payment_channel_id), fn ($q) => $q->where('method', PaymentMethod::ManualTransfer))
            ->whereBetween('paid_at', [$date->startOfDay(), $date->endOfDay()])
            ->where('reconciliation_status', ReconciliationStatus::Unreconciled)
            ->whereNotIn('id', $matchedIds)
            ->get()
            ->each(function (PaymentTransaction $tx) use ($batch): void {
                ReconciliationItem::query()->create([
                    'batch_id' => $batch->id,
                    'payment_transaction_id' => $tx->id,
                    'external_amount' => null,
                    'result' => ReconciliationResult::MissingInStatement,
                ]);
                $tx->forceFill(['reconciliation_status' => ReconciliationStatus::Exception])->save();
            });
    }

    private function refreshCounters(ReconciliationBatch $batch): void
    {
        $items = $batch->items()->get(['result', 'external_amount', 'resolution']);
        $batch->forceFill([
            'total_items' => $items->count(),
            'matched_items' => $items->where('result', ReconciliationResult::Matched)->count(),
            'exception_items' => $items->filter(fn ($i) => $i->result !== ReconciliationResult::Matched && $i->resolution === null)->count(),
            'total_amount' => Money::fromCents($items->sum(fn ($i) => Money::toCents($i->external_amount))),
        ])->save();
    }

    private function assertOpen(ReconciliationBatch $batch): void
    {
        if ($batch->status !== ReconciliationBatchStatus::Open) {
            throw new ConflictException('Batch rekonsiliasi sudah ditutup.');
        }
    }
}
