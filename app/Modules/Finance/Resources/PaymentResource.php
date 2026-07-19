<?php

namespace Modules\Finance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Finance\Models\Payment;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'invoice_period' => $this->whenLoaded('invoice', fn () => $this->invoice?->period),
            'student_name' => $this->whenLoaded('invoice', fn () => $this->invoice?->student?->name),
            'amount' => (string) $this->amount,
            'paid_at' => $this->paid_at->toDateString(),
            'method' => $this->method,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
