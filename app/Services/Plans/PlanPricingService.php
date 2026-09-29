<?php

declare(strict_types=1);

namespace App\Services\Plans;

use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

class PlanPricingService
{
    public const int CACHE_TTL_SECONDS = 600; // 10 minutes

    /**
     * Retrieve a plan by ID, caching for 10 minutes.
     */
    public function getPlan(string $planId): Plan
    {
        return Cache::remember("plan_pricing:{$planId}", self::CACHE_TTL_SECONDS, function () use ($planId): Plan {
            return Plan::findOrFail($planId);
        });
    }

    /**
     * Retrieve cached pricing attributes array for fast computational lookups.
     *
     * @return array{
     *     id: string,
     *     merchant_id: string,
     *     name: string,
     *     slug: string,
     *     invoice_interval: string,
     *     base_price_cents: int,
     *     included_units: int,
     *     overage_unit_price_cents: int,
     *     trial_period_days: int,
     *     is_active: bool
     * }
     */
    public function getPlanPricing(string $planId): array
    {
        return Cache::remember("plan_pricing_data:{$planId}", self::CACHE_TTL_SECONDS, function () use ($planId): array {
            $plan = Plan::findOrFail($planId);

            return [
                'id' => $plan->id,
                'merchant_id' => $plan->merchant_id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'invoice_interval' => $plan->invoice_interval,
                'base_price_cents' => (int) $plan->base_price_cents,
                'included_units' => (int) $plan->included_units,
                'overage_unit_price_cents' => (int) $plan->overage_unit_price_cents,
                'trial_period_days' => (int) $plan->trial_period_days,
                'is_active' => (bool) $plan->is_active,
            ];
        });
    }

    /**
     * Clear all cached keys for a plan.
     */
    public function clearCache(Plan|string $plan): void
    {
        $planId = $plan instanceof Plan ? $plan->id : $plan;

        Cache::forget("plan_pricing:{$planId}");
        Cache::forget("plan_pricing_data:{$planId}");
    }
}
