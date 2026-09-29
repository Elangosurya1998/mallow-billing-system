<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Subscriptions\SwitchCustomerPlanAction;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Services\Plans\PlanPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Phase3PlanCachingAndSwitchingTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private Customer $customer;

    private Plan $starterPlan;

    private Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->merchant = Merchant::create([
            'name' => 'SaaS Grid',
            'slug' => 'saas-grid',
            'email' => 'admin@saasgrid.com',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Acme Labs',
            'email' => 'finance@acmelabs.com',
            'credit_balance_cents' => 0,
        ]);

        // Starter: $30.00/mo (3000 cents), 10,000 included units, 5c overage
        $this->starterPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'invoice_interval' => 'month',
            'base_price_cents' => 3000,
            'included_units' => 10000,
            'overage_unit_price_cents' => 5,
        ]);

        // Pro: $90.00/mo (9000 cents), 50,000 included units, 2c overage
        $this->proPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'invoice_interval' => 'month',
            'base_price_cents' => 9000,
            'included_units' => 50000,
            'overage_unit_price_cents' => 2,
        ]);
    }

    public function test_plan_pricing_service_caches_in_redis_and_observer_invalidates(): void
    {
        $service = app(PlanPricingService::class);

        // 1. Initial fetch caches plan
        $plan = $service->getPlan($this->starterPlan->id);
        $this->assertSame(3000, $plan->base_price_cents);
        $this->assertTrue(Cache::has("plan_pricing:{$this->starterPlan->id}"));

        // 2. Fetch cached pricing data array
        $pricing = $service->getPlanPricing($this->starterPlan->id);
        $this->assertSame(10000, $pricing['included_units']);
        $this->assertTrue(Cache::has("plan_pricing_data:{$this->starterPlan->id}"));

        // 3. Update plan directly in DB triggers PlanObserver
        $this->starterPlan->update([
            'base_price_cents' => 3500,
            'included_units' => 12000,
        ]);

        // 4. Verify cache keys were flushed by PlanObserver
        $this->assertFalse(Cache::has("plan_pricing:{$this->starterPlan->id}"));
        $this->assertFalse(Cache::has("plan_pricing_data:{$this->starterPlan->id}"));

        // 5. Subsequent fetch gets updated values and re-caches
        $updatedPricing = $service->getPlanPricing($this->starterPlan->id);
        $this->assertSame(3500, $updatedPricing['base_price_cents']);
        $this->assertSame(12000, $updatedPricing['included_units']);
        $this->assertTrue(Cache::has("plan_pricing_data:{$this->starterPlan->id}"));
    }

    public function test_switch_customer_plan_action_divides_cycle_into_two_segments_at_exact_midpoint(): void
    {
        $subscription = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);

        // Cycle: June 1, 2026 to July 1, 2026 (30 days)
        $start = Carbon::parse('2026-06-01 00:00:00');
        $end = Carbon::parse('2026-07-01 00:00:00');

        $initialPeriod = SubscriptionPeriod::create([
            'subscription_id' => $subscription->id,
            'plan_id' => $this->starterPlan->id,
            'period_start' => $start,
            'period_end' => $end,
            'status' => 'active',
            'subtotal_cents' => 3000,
            'total_cents' => 3000,
            'prorated_base_price_cents' => 3000,
            'prorated_allowance_units' => 10000,
            'overage_rate_cents' => 5,
        ]);

        // Mid-cycle switch on June 16, 2026 (exact 50% midpoint)
        $switchDate = Carbon::parse('2026-06-16 00:00:00');

        $action = app(SwitchCustomerPlanAction::class);
        $result = $action->execute($subscription, $this->proPlan, $switchDate);

        // 1. Verify Segment 1 (Starter Plan: 50% of time)
        $seg1 = $result->segment1;
        $this->assertSame(1, $seg1->segmentNumber);
        $this->assertSame($this->starterPlan->id, $seg1->plan->id);
        $this->assertEquals(0.5, $seg1->ratio, '', 0.001);
        $this->assertSame(1500, $seg1->proratedBasePriceCents); // 50% of $30.00
        $this->assertSame(5000, $seg1->proratedAllowanceUnits);  // 50% of 10,000 units
        $this->assertSame(5, $seg1->overageUnitPriceCents);

        // 2. Verify Segment 2 (Pro Plan: 50% of time)
        $seg2 = $result->segment2;
        $this->assertSame(2, $seg2->segmentNumber);
        $this->assertSame($this->proPlan->id, $seg2->plan->id);
        $this->assertEquals(0.5, $seg2->ratio, '', 0.001);
        $this->assertSame(4500, $seg2->proratedBasePriceCents); // 50% of $90.00
        $this->assertSame(25000, $seg2->proratedAllowanceUnits); // 50% of 50,000 units
        $this->assertSame(2, $seg2->overageUnitPriceCents);

        // 3. Verify Financial Ledger
        $this->assertSame(1500, $result->unusedCreditCents);  // Unused credit from Starter
        $this->assertSame(4500, $result->newChargeCents);     // Charge for Pro
        $this->assertSame(3000, $result->netAdjustmentCents); // 4500 - 1500 = +$30.00 (Customer owes $30)
        $this->assertTrue($result->isUpgrade);
        $this->assertFalse($result->isDowngrade);
        $this->assertSame('$30.00', $result->formattedNetAdjustment);

        // 4. Verify Database Periods
        $period1 = $result->periodSegment1;
        $this->assertSame('closed', $period1->status);
        $this->assertEquals($switchDate->toDateTimeString(), $period1->period_end->toDateTimeString());
        $this->assertSame(1500, $period1->prorated_base_price_cents);
        $this->assertSame(5000, $period1->prorated_allowance_units);

        $period2 = $result->periodSegment2;
        $this->assertSame('active', $period2->status);
        $this->assertEquals($switchDate->toDateTimeString(), $period2->period_start->toDateTimeString());
        $this->assertEquals($end->toDateTimeString(), $period2->period_end->toDateTimeString());
        $this->assertSame(4500, $period2->prorated_base_price_cents);
        $this->assertSame(25000, $period2->prorated_allowance_units);
        $this->assertSame(2, $period2->overage_rate_cents);

        // 5. Verify Subscription pointer updated to Pro Plan
        $this->assertSame($this->proPlan->id, $result->subscription->plan_id);
    }

    public function test_mid_cycle_downgrade_credits_customer_account(): void
    {
        $subscription = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);

        $start = Carbon::parse('2026-06-01 00:00:00');
        $end = Carbon::parse('2026-07-01 00:00:00');

        SubscriptionPeriod::create([
            'subscription_id' => $subscription->id,
            'plan_id' => $this->proPlan->id,
            'period_start' => $start,
            'period_end' => $end,
            'status' => 'active',
            'subtotal_cents' => 9000,
            'total_cents' => 9000,
            'prorated_base_price_cents' => 9000,
            'prorated_allowance_units' => 50000,
            'overage_rate_cents' => 2,
        ]);

        // Downgrade 10 days in (20 days remaining = 2/3 ratio)
        $switchDate = Carbon::parse('2026-06-11 00:00:00');

        $action = app(SwitchCustomerPlanAction::class);
        $result = $action->execute($subscription, $this->starterPlan, $switchDate);

        $this->assertTrue($result->isDowngrade);
        $this->assertSame(-4000, $result->netAdjustmentCents); // -$40.00 credit
        $this->assertSame('-$40.00', $result->formattedNetAdjustment);

        // Customer's credit balance must increase by $40.00 (4000 cents)
        $this->customer->refresh();
        $this->assertSame(4000, $this->customer->credit_balance_cents);
    }
}
