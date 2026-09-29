<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InvoiceItem
 */
class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'metric_identifier' => $this->metric_identifier,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'subtotal_cents' => $this->subtotal_cents,
            'is_proration' => $this->is_proration,
            'metadata' => $this->metadata,
        ];
    }
}
