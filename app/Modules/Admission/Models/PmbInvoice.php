<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\InvoiceType;
use Modules\Admission\Enums\PmbInvoiceStatus;
use Modules\Admission\Support\Money;

/** Tagihan PMB (KD-03). Nominal final disalin dari konfigurasi saat dibuat.
 *
 *
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property InvoiceType $type
 * @property string $invoice_number
 * @property string $currency
 * @property string $amount_gross
 * @property string $amount_discount
 * @property string $amount_net
 * @property string $amount_paid
 * @property string|null $minimum_for_enrollment
 * @property PmbInvoiceStatus $status
 * @property CarbonImmutable $due_at
 * @property CarbonImmutable|null $paid_at
 * @property bool $needs_resolution
 * @property string|null $resolution_note
 * @property string|null $finance_invoice_id
 * @property string|null $active_key
 * @property int $lock_version
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Application $application
 * @property-read Collection<int, InvoiceItem> $items
 * @property-read Collection<int, PaymentTransaction> $transactions
 */
class PmbInvoice extends PmbModel
{
    protected $table = 'pmb_invoices';

    protected static function booted(): void
    {
        // Satu tagihan aktif per (aplikasi, jenis) — 10 §4.5 `active_key`.
        static::saving(function (self $invoice): void {
            $inactive = in_array($invoice->status, [PmbInvoiceStatus::Cancelled, PmbInvoiceStatus::Refunded], true);
            $invoice->active_key = $inactive ? null : $invoice->application_id.':'.$invoice->type->value;
        });
    }

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => PmbInvoiceStatus::class,
            'amount_gross' => 'decimal:2',
            'amount_discount' => 'decimal:2',
            'amount_net' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'minimum_for_enrollment' => 'decimal:2',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'needs_resolution' => 'boolean',
            'lock_version' => 'integer',
        ];
    }

    public function outstanding(): string
    {
        return Money::sub((string) $this->amount_net, (string) $this->amount_paid);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    /**
     * @return HasMany<PaymentTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'invoice_id')->latest();
    }
}
