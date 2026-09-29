<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\DTOs\ProrationResultDto;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Services\Proration\CalculateProrationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChangeSubscriptionPlanAction
{
    public function __construct(
        private readonly CalculateProrationService $prorationService,
    ) {}

    /**
     * Change plan with instant proration and tenant credit rollover.
     *
     * @return array{subscription: Subscription, proration: ProrationResultDto}
     */
    public function execute(
        Subscription $subscription,
        Plan $newPlan,
        ?Carbon $effectiveAt = null,
        ?int $newQuantity = null,
    ): array {
        return DB::transaction(function () use ($subscription, $newPlan, $effectiveAt, $newQuantity): array {
            $effectiveAt = $effectiveAt ?? Carbon::now();
            $quantity = $newQuantity ?? $subscription->quantity;

            $currentPlan = $subscription->plan;

            $periodStart = $subscription->current_period_start ?? Carbon::now()->startOfMonth();
            $periodEnd = $subscription->current_period_end ?? Carbon::parse($periodStart)->addMonth();

            // Calculate proration
            $proration = $this->prorationService->calculate(
                currentPlan: $currentPlan,
                newPlan: $newPlan,
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                prorationAt: $effectiveAt,
                quantity: $quantity,
            );

            // Roll credit/debit into customer's credit_balance_cents
            $customer = $subscription->customer;
            if ($customer) {
                if ($proration->isCredit) {
                    $creditToAdd = abs($proration->netAdjustmentCents);
                    $customer->increment('credit_balance_cents', $creditToAdd);
                } elseif ($proration->isDebit && $customer->credit_balance_cents > 0) {
                    $deductible = min($customer->credit_balance_cents, $proration->netAdjustmentCents);
                    $customer->decrement('credit_balance_cents', $deductible);
                }
            }

            // Segment active period if one exists
            $currentPeriod = $subscription->currentPeriod();
            if ($currentPeriod) {
                $currentPeriod->update([
                    'status' => 'closed',
                    'period_end' => $effectiveAt,
                ]);

                SubscriptionPeriod::create([
                    'subscription_id' => $subscription->id,
                    'plan_id' => $newPlan->id,
                    'period_start' => $effectiveAt,
                    'period_end' => $periodEnd,
                    'status' => 'active',
                    'subtotal_cents' => $newPlan->base_price_cents * $quantity,
                    'total_cents' => $newPlan->base_price_cents * $quantity,
                    'prorated_base_price_cents' => $proration->newPlanChargeCents,
                    'prorated_allowance_units' => (int) round($newPlan->included_units * $quantity),
                    'overage_rate_cents' => $newPlan->overage_unit_price_cents,
                ]);
            }

            // Update subscription
            $subscription->update([
                'plan_id' => $newPlan->id,
                'quantity' => $quantity,
            ]);

            return [
                'subscription' => $subscription->fresh(['plan', 'merchant', 'customer']),
                'proration' => $proration,
            ];
        });
    }
}
