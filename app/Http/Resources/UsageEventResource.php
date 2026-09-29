<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UsageEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UsageEvent
 */
class UsageEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id ?? $this->tenant_id ?? null,
            'customer_id' => $this->customer_id ?? null,
            'metric_identifier' => $this->metric_identifier,
            'quantity' => $this->quantity,
            'idempotency_key' => $this->idempotency_key,
            'timestamp' => $this->timestamp->toIso8601String(),
            'properties' => $this->properties,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
