<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id ?? $this->tenant_id,
            'tenant_id' => $this->merchant_id ?? $this->tenant_id,
            'customer_id' => $this->customer_id,
            'subscription_id' => $this->subscription_id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'subtotal_cents' => $this->subtotal_cents,
            'tax_cents' => $this->tax_cents,
            'total_cents' => $this->total_cents,
            'amount_paid_cents' => $this->amount_paid_cents,
            'amount_remaining_cents' => $this->amount_remaining_cents,
            'due_date' => $this->due_date?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'billing_reason' => $this->billing_reason,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'customer' => $this->whenLoaded('customer'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
