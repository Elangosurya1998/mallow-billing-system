<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'invoice_interval' => $this->invoice_interval,
            'base_price_cents' => $this->base_price_cents,
            'trial_period_days' => $this->trial_period_days,
            'is_active' => $this->is_active,
            'price_tiers' => PriceTierResource::collection($this->whenLoaded('priceTiers')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
