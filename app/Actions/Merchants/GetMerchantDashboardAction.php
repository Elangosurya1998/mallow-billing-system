<?php

declare(strict_types=1);

namespace App\Actions\Merchants;

use App\DTOs\MerchantDashboardDto;
use App\Models\DailyUsageSummary;
use App\Models\Merchant;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class GetMerchantDashboardAction
{
    public function execute(Merchant $merchant, ?CarbonInterface $asOf = null): MerchantDashboardDto
    {
        $asOfDate = $asOf ? Carbon::parse($asOf) : Carbon::now();

        // 1. Fetch active subscriptions with customer, plan, and periods
        $subscriptions = $merchant->subscriptions()
            ->with(['customer', 'plan', 'periods'])
            ->where('status', 'active')
            ->get();

        $totalUsageUnits = 0;
        $totalIncludedUnits = 0;
        $totalIncurredOverageCents = 0;
        $totalProjectedOverageCents = 0;
        $customerUsageStats = [];

        foreach ($subscriptions as $subscription) {
            $customer = $subscription->customer;
            $plan = $subscription->plan;

            // Find current active period covering or closest to asOfDate
            $activePeriod = $subscription->periods
                ->where('status', 'active')
                ->sortByDesc('period_start')
                ->first();

            $periodStart = $activePeriod?->period_start ?? $asOfDate->copy()->startOfMonth();
            $periodEnd = $activePeriod?->period_end ?? $asOfDate->copy()->endOfMonth();

            $allowance = $activePeriod?->prorated_allowance_units > 0
                ? $activePeriod->prorated_allowance_units
                : ($plan->included_units * $subscription->quantity);

            $overageRateCents = $activePeriod?->overage_rate_cents > 0
                ? $activePeriod->overage_rate_cents
                : (int) $plan->overage_unit_price_cents;

            // Roll-up usage from pre-aggregated daily summaries
            $usedUnits = (int) DailyUsageSummary::query()
                ->where('customer_id', $customer->id)
                ->whereBetween('usage_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ])
                ->sum('total_quantity');

            $totalUsageUnits += $usedUnits;
            $totalIncludedUnits += $allowance;

            // Incurred overage calculations
            $incurredOverageUnits = max(0, $usedUnits - $allowance);
            $incurredOverageCents = $incurredOverageUnits * $overageRateCents;
            $totalIncurredOverageCents += $incurredOverageCents;

            // Projected cycle-end overage calculations based on elapsed cycle days
            $totalCycleDays = max(1, $periodStart->diffInDays($periodEnd));
            $cycleEffectiveAsOf = $asOfDate->gt($periodEnd) ? $periodEnd : ($asOfDate->lt($periodStart) ? $periodStart : $asOfDate);
            $elapsedDays = max(1, $periodStart->diffInDays($cycleEffectiveAsOf));

            $projectedUnits = (int) round(($usedUnits / $elapsedDays) * $totalCycleDays);
            $projectedOverageUnits = max(0, $projectedUnits - $allowance);
            $projectedOverageCents = $projectedOverageUnits * $overageRateCents;
            $totalProjectedOverageCents += $projectedOverageCents;

            $pctConsumed = $allowance > 0
                ? round(($usedUnits / $allowance) * 100, 2)
                : ($usedUnits > 0 ? 100.0 : 0.0);

            $customerUsageStats[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'plan_name' => $plan->name,
                'used_units' => $usedUnits,
                'allowance_units' => $allowance,
                'percentage_consumed' => $pctConsumed,
                'incurred_overage_cents' => $incurredOverageCents,
                'projected_overage_cents' => $projectedOverageCents,
                'currency' => $merchant->currency,
            ];
        }

        // Top 5 customers sorted by percentage of allowance consumed DESC
        usort($customerUsageStats, function ($a, $b) {
            if ($b['percentage_consumed'] === $a['percentage_consumed']) {
                return $b['used_units'] <=> $a['used_units'];
            }

            return $b['percentage_consumed'] <=> $a['percentage_consumed'];
        });

        $top5Customers = array_slice($customerUsageStats, 0, 5);

        // Churn-risk customers (>50% Month-over-Month drop)
        $currWindowStart = $asOfDate->copy()->subDays(30)->toDateString();
        $currWindowEnd = $asOfDate->toDateString();
        $prevWindowStart = $asOfDate->copy()->subDays(60)->toDateString();
        $prevWindowEnd = $asOfDate->copy()->subDays(31)->toDateString();

        $churnRiskCustomers = [];

        foreach ($merchant->customers as $customer) {
            $prevUsage = (int) DailyUsageSummary::query()
                ->where('customer_id', $customer->id)
                ->whereBetween('usage_date', [$prevWindowStart, $prevWindowEnd])
                ->sum('total_quantity');

            $currUsage = (int) DailyUsageSummary::query()
                ->where('customer_id', $customer->id)
                ->whereBetween('usage_date', [$currWindowStart, $currWindowEnd])
                ->sum('total_quantity');

            if ($prevUsage > 0) {
                $dropUnits = $prevUsage - $currUsage;
                if ($dropUnits > 0) {
                    $dropPercentage = round(($dropUnits / $prevUsage) * 100, 2);

                    if ($dropPercentage > 50.0) {
                        $churnRiskCustomers[] = [
                            'customer_id' => $customer->id,
                            'customer_name' => $customer->name,
                            'previous_period_usage' => $prevUsage,
                            'current_period_usage' => $currUsage,
                            'drop_percentage' => $dropPercentage,
                            'risk_level' => 'high',
                        ];
                    }
                }
            }
        }

        usort($churnRiskCustomers, fn ($a, $b) => $b['drop_percentage'] <=> $a['drop_percentage']);

        $overallPct = $totalIncludedUnits > 0
            ? round(($totalUsageUnits / $totalIncludedUnits) * 100, 2)
            : 0.0;

        return new MerchantDashboardDto(
            merchantId: $merchant->id,
            merchantName: $merchant->name,
            currency: $merchant->currency,
            asOfDate: $asOfDate->toIso8601String(),
            cycleUsage: [
                'total_usage_units' => $totalUsageUnits,
                'total_included_units' => $totalIncludedUnits,
                'consumption_percentage' => $overallPct,
            ],
            projectedOverageRevenue: [
                'incurred_cents' => $totalIncurredOverageCents,
                'incurred_formatted' => sprintf('%s %.2f', $merchant->currency, $totalIncurredOverageCents / 100),
                'projected_cents' => $totalProjectedOverageCents,
                'projected_formatted' => sprintf('%s %.2f', $merchant->currency, $totalProjectedOverageCents / 100),
            ],
            topCustomersByUsage: $top5Customers,
            churnRiskCustomers: $churnRiskCustomers,
        );
    }
}
