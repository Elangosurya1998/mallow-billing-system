<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsageSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $usageDate = $this->usage_date instanceof \DateTimeInterface
            ? $this->usage_date->format('Y-m-d')
            : (string) $this->usage_date;

        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id ?? $this->tenant_id ?? null,
            'tenant_id' => $this->merchant_id ?? $this->tenant_id ?? null,
            'customer_id' => $this->customer_id ?? null,
            'metric_identifier' => $this->metric_identifier,
            'usage_date' => $usageDate,
            'total_quantity' => (int) $this->total_quantity,
            'event_count' => (int) $this->event_count,
            'last_aggregated_at' => $this->last_aggregated_at instanceof \DateTimeInterface
                ? $this->last_aggregated_at->toIso8601String()
                : ($this->updated_at?->toIso8601String() ?? now()->toIso8601String()),
        ];
    }
}
