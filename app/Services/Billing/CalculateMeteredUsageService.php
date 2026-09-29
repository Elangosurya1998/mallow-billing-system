<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\PriceTier;
use App\Models\Tenant;
use App\Models\UsageSummary;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CalculateMeteredUsageService
{
    /**
     * Calculate metered usage charges based on plan tiers and daily pre-aggregates.
     *
     * @return array<int, array{
     *     metric_identifier: string,
     *     quantity: int,
     *     unit_price_cents: int,
     *     subtotal_cents: int,
     *     description: string,
     *     metadata: array<string, mixed>
     * }>
     */
    public function calculate(
        Tenant $tenant,
        Plan $plan,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
    ): array {
        $items = [];
        $metrics = $plan->priceTiers()->pluck('metric_identifier')->unique();

        foreach ($metrics as $metric) {
            $totalQuantity = (int) UsageSummary::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('metric_identifier', $metric)
                ->whereBetween('usage_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->sum('total_quantity');

            if ($totalQuantity <= 0) {
                continue;
            }

            $tiers = $plan->tiersForMetric($metric)->get();
            if ($tiers->isEmpty()) {
                continue;
            }

            $tierMode = $tiers->first()->tier_mode;
            $subtotalCents = 0;
            $tierBreakdown = [];

            if ($tierMode === 'volume') {
                $subtotalCents = $this->calculateVolume($tiers, $totalQuantity, $tierBreakdown);
            } else {
                // Default: graduated
                $subtotalCents = $this->calculateGraduated($tiers, $totalQuantity, $tierBreakdown);
            }

            $effectiveUnitPrice = (int) round($subtotalCents / $totalQuantity, 0, PHP_ROUND_HALF_UP);

            $items[] = [
                'metric_identifier' => $metric,
                'quantity' => $totalQuantity,
                'unit_price_cents' => $effectiveUnitPrice,
                'subtotal_cents' => $subtotalCents,
                'description' => sprintf(
                    'Usage overage for %s (%s units)',
                    $metric,
                    number_format($totalQuantity)
                ),
                'metadata' => [
                    'tier_mode' => $tierMode,
                    'breakdown' => $tierBreakdown,
                    'period_start' => $periodStart->toIso8601String(),
                    'period_end' => $periodEnd->toIso8601String(),
                ],
            ];
        }

        return $items;
    }

    /**
     * @param  Collection<int, PriceTier>  $tiers
     * @param  array<int, mixed>  $breakdown
     */
    private function calculateGraduated($tiers, int $totalQuantity, array &$breakdown): int
    {
        $subtotal = 0;

        foreach ($tiers as $tier) {
            $first = $tier->first_unit;
            $last = $tier->last_unit;

            if ($totalQuantity < $first) {
                break;
            }

            $unitsInBracket = ($last !== null)
                ? min($totalQuantity, $last) - $first + 1
                : $totalQuantity - $first + 1;

            if ($unitsInBracket > 0) {
                $bracketCost = ($unitsInBracket * $tier->unit_price_cents) + $tier->flat_fee_cents;
                $subtotal += $bracketCost;

                $breakdown[] = [
                    'bracket' => sprintf('%d - %s', $first, $last ?? 'unlimited'),
                    'units' => $unitsInBracket,
                    'unit_price_cents' => $tier->unit_price_cents,
                    'cost_cents' => $bracketCost,
                ];
            }
        }

        return $subtotal;
    }

    /**
     * @param  Collection<int, PriceTier>  $tiers
     * @param  array<int, mixed>  $breakdown
     */
    private function calculateVolume($tiers, int $totalQuantity, array &$breakdown): int
    {
        $matchingTier = null;

        foreach ($tiers as $tier) {
            $first = $tier->first_unit;
            $last = $tier->last_unit;

            if ($totalQuantity >= $first && ($last === null || $totalQuantity <= $last)) {
                $matchingTier = $tier;
                break;
            }
        }

        if (! $matchingTier) {
            $matchingTier = $tiers->last();
        }

        $cost = ($totalQuantity * $matchingTier->unit_price_cents) + $matchingTier->flat_fee_cents;

        $breakdown[] = [
            'bracket' => sprintf('%d - %s', $matchingTier->first_unit, $matchingTier->last_unit ?? 'unlimited'),
            'units' => $totalQuantity,
            'unit_price_cents' => $matchingTier->unit_price_cents,
            'cost_cents' => $cost,
        ];

        return $cost;
    }
}
