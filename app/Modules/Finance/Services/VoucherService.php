<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Finance\Enums\AdjustmentType;
use Modules\Finance\Enums\AidCalcType;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\RedemptionStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Enums\VoucherStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Models\Voucher;
use Modules\Finance\Models\VoucherRedemption;
use Modules\Finance\Support\KeuSettings;
use Modules\Finance\Support\Money;

/**
 * Voucher tagihan (F-06, BR-KEU-27..29): ditukar pada tagihan belum dibayar
 * tanpa transaksi berjalan; kuota dijaga row lock; percobaan gagal dibatasi.
 * Potongan diterapkan sebagai penyesuaian `voucher` yang tercatat.
 */
class VoucherService
{
    public function __construct(
        private readonly AdjustmentService $adjustments,
        private readonly KeuSettings $settings,
    ) {}

    public function redeem(Invoice $invoice, string $code, User $actor): VoucherRedemption
    {
        $limiterKey = 'keu-voucher:'.$actor->id;

        if (RateLimiter::tooManyAttempts($limiterKey, max(1, $this->settings->int('keu.voucher.max_failed_attempts')))) {
            throw new ConflictException('Terlalu banyak percobaan kode voucher yang salah. Coba lagi dalam 30 menit.', 'VOUCHER_THROTTLED');
        }

        try {
            return DB::transaction(function () use ($invoice, $code, $actor): VoucherRedemption {
                $voucher = Voucher::query()->where('code', strtoupper(trim($code)))->lockForUpdate()->first();

                if ($voucher === null) {
                    throw new ConflictException('Kode voucher tidak dikenal.', 'VOUCHER_INVALID');
                }

                $invoice = Invoice::query()->with('student')->lockForUpdate()->findOrFail($invoice->id);
                $cents = $this->validateAndCompute($voucher, $invoice);

                $adjustment = $this->adjustments->applySystem($invoice, AdjustmentType::Voucher, $cents, 'VOUCHER', "Voucher {$voucher->code} — {$voucher->name}", 'voucher_redemption', $actor, InvoiceLineType::Voucher);
                $voucher->increment('quota_used');

                return VoucherRedemption::query()->create([
                    'university_id' => $invoice->university_id,
                    'voucher_id' => $voucher->id,
                    'student_id' => $invoice->student_id,
                    'invoice_id' => $invoice->id,
                    'discount_amount' => Money::fromCents($cents),
                    'status' => RedemptionStatus::Applied,
                    'redeemed_by' => $actor->id,
                ]);
            });
        } catch (ConflictException $e) {
            if (in_array($e->errorCode, ['VOUCHER_INVALID', 'VOUCHER_NOT_APPLICABLE'], true)) {
                RateLimiter::hit($limiterKey, 1800);
            }

            throw $e;
        }
    }

    public function reverse(VoucherRedemption $redemption, User $actor): VoucherRedemption
    {
        return DB::transaction(function () use ($redemption, $actor): VoucherRedemption {
            $redemption = VoucherRedemption::query()->lockForUpdate()->findOrFail($redemption->id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($redemption->invoice_id);

            if ($redemption->status !== RedemptionStatus::Applied) {
                throw new ConflictException('Penukaran voucher ini sudah dibatalkan.');
            }

            if (Money::isPositive($invoice->paid_amount)) {
                throw new ConflictException('Voucher tidak dapat dibatalkan karena tagihan sudah menerima pembayaran (BR-KEU-28).');
            }

            $this->adjustments->applySystem($invoice, AdjustmentType::Increase, Money::toCents($redemption->discount_amount), 'VOUCHER_REVERSAL', 'Pembatalan voucher', 'voucher_redemption', $actor);
            Voucher::query()->whereKey($redemption->voucher_id)->where('quota_used', '>', 0)->decrement('quota_used');
            $redemption->forceFill(['status' => RedemptionStatus::Reversed, 'reversed_at' => now(), 'reversed_by' => $actor->id])->save();

            return $redemption;
        });
    }

    private function validateAndCompute(Voucher $voucher, Invoice $invoice): int
    {
        $now = now();
        $notApplicable = fn (string $msg) => new ConflictException($msg, 'VOUCHER_NOT_APPLICABLE');

        if ($voucher->status !== VoucherStatus::Active || $now->lessThan($voucher->valid_from) || $now->greaterThan($voucher->valid_until)) {
            throw $notApplicable('Voucher tidak aktif atau di luar masa berlaku.');
        }

        if ($voucher->quota_total !== null && $voucher->quota_used >= $voucher->quota_total) {
            throw new ConflictException('Kuota voucher sudah habis.', 'VOUCHER_EXHAUSTED');
        }

        $used = VoucherRedemption::query()->where('voucher_id', $voucher->id)->where('student_id', $invoice->student_id)->where('status', RedemptionStatus::Applied)->count();

        if ($used >= $voucher->per_student_limit) {
            throw $notApplicable('Batas pemakaian voucher untuk Anda sudah tercapai.');
        }

        if ($invoice->status !== InvoiceStatus::Unpaid || Money::isPositive($invoice->paid_amount)) {
            throw $notApplicable('Voucher hanya dapat dipakai pada tagihan yang belum dibayar.');
        }

        if ($now->greaterThan($invoice->due_date->endOfDay())) {
            throw $notApplicable('Tagihan sudah melewati jatuh tempo.');
        }

        $pending = PaymentTransaction::query()->whereIn('target_id', [$invoice->id, ...$invoice->installments()->pluck('id')->all()])->whereIn('status', [TransactionStatus::Pending, TransactionStatus::AwaitingVerification])->exists();

        if ($pending) {
            throw new ConflictException('Batalkan dahulu transaksi pembayaran yang sedang berjalan untuk tagihan ini.', 'PENDING_TRANSACTION_EXISTS');
        }

        if (! empty($voucher->bill_groups) && ! in_array($invoice->bill_group->value, $voucher->bill_groups, true)) {
            throw $notApplicable('Voucher tidak berlaku untuk jenis tagihan ini.');
        }

        $scope = $voucher->scope ?? [];
        $student = $invoice->student;

        if ((! empty($scope['study_program_ids']) && ! in_array($student->study_program_id, $scope['study_program_ids'], true))
            || (! empty($scope['admission_years']) && ! in_array((int) $student->admission_year, array_map('intval', $scope['admission_years']), true))
            || (! empty($scope['admission_path_codes']) && ! in_array($student->admission_path_code, $scope['admission_path_codes'], true))) {
            throw $notApplicable('Voucher tidak berlaku untuk program studi/angkatan/jalur Anda.');
        }

        $amount = Money::toCents($invoice->amount);

        if ($voucher->min_invoice_amount !== null && $amount < Money::toCents($voucher->min_invoice_amount)) {
            throw $notApplicable('Nilai tagihan belum memenuhi syarat minimal voucher.');
        }

        $unit = max(1, $this->settings->int('keu.rounding.unit')) * 100;
        $cents = match ($voucher->discount_type) {
            AidCalcType::Percent => Money::percentCents($amount, (float) $voucher->discount_value, $unit),
            AidCalcType::Amount => Money::toCents($voucher->discount_value),
            AidCalcType::Full => $amount,
        };

        if ($voucher->max_discount !== null) {
            $cents = min($cents, Money::toCents($voucher->max_discount));
        }

        return min($cents, $amount);
    }
}
