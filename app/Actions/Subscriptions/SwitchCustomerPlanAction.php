<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\DTOs\PlanSegmentDto;
use App\DTOs\PlanSwitchResultDto;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Services\Plans\PlanPricingService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SwitchCustomerPlanAction
{
    public function __construct(
        private readonly PlanPricingService $pricingService,
    ) {}

    /**
     * Switch customer plan mid-cycle, segmenting the billing period and prorating
     * base fees and allowances independently.
     */
    public function execute(
        Subscription $subscription,
        Plan|string $newPlan,
        ?CarbonInterface $switchAt = null,
    ): PlanSwitchResultDto {
        $resolvedNewPlan = $newPlan instanceof Plan
            ? $newPlan
            : $this->pricingService->getPlan($newPlan);

        return DB::transaction(function () use ($subscription, $resolvedNewPlan, $switchAt): PlanSwitchResultDto {
            $switchTime = $switchAt ? Carbon::parse($switchAt) : Carbon::now();
            $subscription->refresh();
            $oldPlan = $subscription->plan;
            $quantity = $subscription->quantity;

            // 1. Resolve current active billing period
            $currentPeriod = $subscription->periods()
                ->where('status', 'active')
                ->latest('period_start')
                ->first();
            if (! $currentPeriod) {
                $start = $subscription->created_at ?? Carbon::now();
                $end = $start->copy()->addMonth();

                $currentPeriod = SubscriptionPeriod::create([
                    'subscription_id' => $subscription->id,
                    'plan_id' => $oldPlan->id,
                    'period_start' => $start,
                    'period_end' => $end,
                    'status' => 'active',
                    'subtotal_cents' => $oldPlan->base_price_cents * $quantity,
                    'total_cents' => $oldPlan->base_price_cents * $quantity,
                    'prorated_base_price_cents' => $oldPlan->base_price_cents * $quantity,
                    'prorated_allowance_units' => $oldPlan->included_units * $quantity,
                    'overage_rate_cents' => $oldPlan->overage_unit_price_cents,
                ]);
            }

            $cycleStart = $currentPeriod->period_start;
            $cycleEnd = $currentPeriod->period_end;

            // Clamp switch time to cycle interval
            if ($switchTime->lt($cycleStart)) {
                $switchTime = $cycleStart->copy();
            } elseif ($switchTime->gt($cycleEnd)) {
                $switchTime = $cycleEnd->copy();
            }

            $totalSeconds = max(1, $cycleEnd->getTimestamp() - $cycleStart->getTimestamp());

            // 2. Segment 1 (Old Plan) Calculations
            $t1Seconds = max(0, $switchTime->getTimestamp() - $cycleStart->getTimestamp());
            $ratio1 = $t1Seconds / $totalSeconds;

            $fullOldBase = $oldPlan->base_price_cents * $quantity;
            $fullOldAllowance = $oldPlan->included_units * $quantity;

            $proratedBase1 = (int) round($fullOldBase * $ratio1, 0, PHP_ROUND_HALF_UP);
            $proratedAllowance1 = (int) round($fullOldAllowance * $ratio1, 0, PHP_ROUND_HALF_UP);

            $segment1Dto = new PlanSegmentDto(
                segmentNumber: 1,
                plan: $oldPlan,
                start: $cycleStart,
                end: $switchTime,
                durationSeconds: $t1Seconds,
                ratio: $ratio1,
                proratedBasePriceCents: $proratedBase1,
                proratedAllowanceUnits: $proratedAllowance1,
                overageUnitPriceCents: (int) $oldPlan->overage_unit_price_cents,
            );

            // 3. Segment 2 (New Plan) Calculations
            $t2Seconds = max(0, $cycleEnd->getTimestamp() - $switchTime->getTimestamp());
            $ratio2 = $t2Seconds / $totalSeconds;

            $fullNewBase = $resolvedNewPlan->base_price_cents * $quantity;
            $fullNewAllowance = $resolvedNewPlan->included_units * $quantity;

            $proratedBase2 = (int) round($fullNewBase * $ratio2, 0, PHP_ROUND_HALF_UP);
            $proratedAllowance2 = (int) round($fullNewAllowance * $ratio2, 0, PHP_ROUND_HALF_UP);

            $segment2Dto = new PlanSegmentDto(
                segmentNumber: 2,
                plan: $resolvedNewPlan,
                start: $switchTime,
                end: $cycleEnd,
                durationSeconds: $t2Seconds,
                ratio: $ratio2,
                proratedBasePriceCents: $proratedBase2,
                proratedAllowanceUnits: $proratedAllowance2,
                overageUnitPriceCents: (int) $resolvedNewPlan->overage_unit_price_cents,
            );

            // 4. Financial Proration Ledger (Integer Cents)
            // Unused credit from Old Plan
            $unusedCreditCents = (int) round($fullOldBase * $ratio2, 0, PHP_ROUND_HALF_UP);
            // Charge for New Plan
            $newChargeCents = $proratedBase2;
            // Net Adjustment: positive = customer owes, negative = credit to customer
            $netAdjustmentCents = $newChargeCents - $unusedCreditCents;

            // Roll credit into customer's balance if downgrade
            $customer = $subscription->customer;
            if ($netAdjustmentCents < 0) {
                $creditToAdd = abs($netAdjustmentCents);
                $customer->increment('credit_balance_cents', $creditToAdd);
            } elseif ($netAdjustmentCents > 0 && $customer->credit_balance_cents > 0) {
                $deductible = min($customer->credit_balance_cents, $netAdjustmentCents);
                $customer->decrement('credit_balance_cents', $deductible);
            }

            // 5. Database Segmentation
            // Close Segment 1
            $currentPeriod->update([
                'plan_id' => $oldPlan->id,
                'period_end' => $switchTime,
                'status' => 'closed',
                'prorated_base_price_cents' => $proratedBase1,
                'prorated_allowance_units' => $proratedAllowance1,
                'overage_rate_cents' => $oldPlan->overage_unit_price_cents,
                'subtotal_cents' => $proratedBase1,
                'total_cents' => $proratedBase1,
            ]);

            // Open Segment 2
            $segment2Period = SubscriptionPeriod::create([
                'subscription_id' => $subscription->id,
                'plan_id' => $resolvedNewPlan->id,
                'period_start' => $switchTime,
                'period_end' => $cycleEnd,
                'status' => 'active',
                'prorated_base_price_cents' => $proratedBase2,
                'prorated_allowance_units' => $proratedAllowance2,
                'overage_rate_cents' => $resolvedNewPlan->overage_unit_price_cents,
                'subtotal_cents' => $proratedBase2,
                'total_cents' => $proratedBase2,
            ]);

            // Update Subscription pointer
            $subscription->update([
                'plan_id' => $resolvedNewPlan->id,
            ]);

            return new PlanSwitchResultDto(
                subscription: $subscription->fresh(['plan', 'customer', 'periods']),
                segment1: $segment1Dto,
                segment2: $segment2Dto,
                unusedCreditCents: $unusedCreditCents,
                newChargeCents: $newChargeCents,
                netAdjustmentCents: $netAdjustmentCents,
                periodSegment1: $currentPeriod->fresh(),
                periodSegment2: $segment2Period->fresh(),
            );
        });
    }
}
