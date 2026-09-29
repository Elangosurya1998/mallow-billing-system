<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subscription
 */
class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentPeriod = $this->relationLoaded('periods') ? $this->periods->first() : $this->currentPeriod();

        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id ?? $this->tenant_id,
            'tenant_id' => $this->merchant_id ?? $this->tenant_id,
            'customer_id' => $this->customer_id,
            'plan_id' => $this->plan_id,
            'status' => $this->status,
            'quantity' => $this->quantity,
            'current_period_start' => $this->current_period_start?->toIso8601String() ?? $currentPeriod?->period_start?->toIso8601String(),
            'current_period_end' => $this->current_period_end?->toIso8601String() ?? $currentPeriod?->period_end?->toIso8601String(),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'cancel_at_period_end' => (bool) $this->cancel_at_period_end,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
