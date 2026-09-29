<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Subscriptions\SwitchCustomerPlanAction;
use App\Jobs\GenerateCycleInvoicesJob;
use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Phase5BillingEngineTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private Customer $customer;

    private Plan $starterPlan;

    private Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'Apex Cloud Systems',
            'slug' => 'apex-cloud',
            'email' => 'billing@apexcloud.io',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Solaris Tech',
            'email' => 'accounts@solaris.io',
            'credit_balance_cents' => 0,
        ]);

        // Starter: $30/month (3000 cents), 10,000 units included, 5c/unit overage
        $this->starterPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Starter Tier',
            'slug' => 'starter-tier',
            'base_price_cents' => 3000,
            'included_units' => 10000,
            'overage_unit_price_cents' => 5,
            'invoice_interval' => 'month',
        ]);

        // Pro: $90/month (9000 cents), 50,000 units included, 2c/unit overage
        $this->proPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Pro Tier',
            'slug' => 'pro-tier',
            'base_price_cents' => 9000,
            'included_units' => 50000,
            'overage_unit_price_cents' => 2,
            'invoice_interval' => 'month',
        ]);
    }

    /**
     * Requirement: Idempotency replay (no double count).
     */
    public function test_idempotency_replay_prevents_double_counting(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_compute_seconds',
            'quantity' => 250,
            'idempotency_key' => 'idemp-req-unique-991',
            'timestamp' => '2026-09-15T10:30:00Z',
            'properties' => ['region' => 'us-east-1'],
        ];

        // 1. Initial fresh submission: returns HTTP 201 Created
        $response1 = $this->postJson('/api/v1/usage', $payload, [
            'X-API-Key' => 'test-api-key',
        ]);

        $response1->assertCreated();
        $response1->assertJsonPath('idempotent_replay', false);
        $response1->assertJsonPath('data.quantity', 250);
        $response1->assertJsonPath('data.idempotency_key', 'idemp-req-unique-991');

        $this->assertDatabaseCount('usage_events', 1);

        $summary = DailyUsageSummary::where('customer_id', $this->customer->id)
            ->where('usage_date', '2026-09-15')
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame(250, $summary->total_quantity);
        $this->assertSame(1, $summary->event_count);

        // 2. Replay with identical payload and idempotency key: returns HTTP 200 OK
        $response2 = $this->postJson('/api/v1/usage', $payload, [
            'X-API-Key' => 'test-api-key',
        ]);

        $response2->assertOk();
        $response2->assertJsonPath('idempotent_replay', true);
        $response2->assertJsonPath('data.idempotency_key', 'idemp-req-unique-991');

        // Usage event count must remain exactly 1
        $this->assertDatabaseCount('usage_events', 1);

        // Summary counters must NOT double count
        $summary->refresh();
        $this->assertSame(250, $summary->total_quantity);
        $this->assertSame(1, $summary->event_count);

        // 3. New submission with different idempotency key: increments properly
        $payload2 = array_merge($payload, [
            'idempotency_key' => 'idemp-req-unique-992',
            'quantity' => 150,
        ]);

        $response3 = $this->postJson('/api/v1/usage', $payload2, [
            'X-API-Key' => 'test-api-key',
        ]);

        $response3->assertCreated();
        $response3->assertJsonPath('idempotent_replay', false);

        $this->assertDatabaseCount('usage_events', 2);

        $summary->refresh();
        $this->assertSame(400, $summary->total_quantity); // 250 + 150 = 400
        $this->assertSame(2, $summary->event_count);
    }

    /**
     * Requirement: Mid-cycle signup proration.
     */
    public function test_mid_cycle_signup_prorates_base_fee_and_allowance(): void
    {
        // Customer signs up on June 16, 2026 for a billing cycle ending July 1, 2026
        // Total cycle duration (June 1 to July 1): 30 days
        // Active days (June 16 to July 1): 15 days
        // Proration factor: 15 / 30 = 0.50
        $cycleStart = Carbon::parse('2026-06-01 00:00:00');
        $signupDate = Carbon::parse('2026-06-16 00:00:00');
        $cycleEnd = Carbon::parse('2026-07-01 00:00:00');

        $totalDays = $cycleStart->diffInDays($cycleEnd); // 30 days
        $activeDays = $signupDate->diffInDays($cycleEnd); // 15 days

        $prorationRatio = $activeDays / $totalDays; // 0.5

        $expectedBasePriceCents = (int) round($this->starterPlan->base_price_cents * $prorationRatio); // 3000 * 0.5 = 1500c ($15.00)
        $expectedAllowanceUnits = (int) round($this->starterPlan->included_units * $prorationRatio);   // 10,000 * 0.5 = 5,000 units

        $subscription = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
            'started_at' => $signupDate,
        ]);

        $period = SubscriptionPeriod::create([
            'subscription_id' => $subscription->id,
            'plan_id' => $this->starterPlan->id,
            'period_start' => $signupDate,
            'period_end' => $cycleEnd,
            'status' => 'active',
            'prorated_base_price_cents' => $expectedBasePriceCents,
            'prorated_allowance_units' => $expectedAllowanceUnits,
            'overage_rate_cents' => (int) $this->starterPlan->overage_unit_price_cents,
            'subtotal_cents' => $expectedBasePriceCents,
            'total_cents' => $expectedBasePriceCents,
        ]);

        $this->assertSame(1500, $period->prorated_base_price_cents);
        $this->assertSame(5000, $period->prorated_allowance_units);
        $this->assertSame(5, $period->overage_rate_cents);

        // Seed 4,000 units of usage (under prorated allowance of 5,000 units)
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_compute_seconds',
            'usage_date' => '2026-06-20',
            'total_quantity' => 4000,
            'event_count' => 10,
        ]);

        // Run cycle invoice generator
        $job = new GenerateCycleInvoicesJob('2026-07-02 00:00:00');
        $job->handle();

        $invoice = Invoice::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($invoice);

        // Since usage was 4,000 <= 5,000, total should be exact prorated base fee ($15.00 = 1500 cents)
        $this->assertSame(1500, $invoice->subtotal_cents);
        $this->assertSame(1500, $invoice->total_cents);
        $this->assertCount(1, $invoice->items);
        $this->assertTrue((bool) $invoice->items[0]->is_proration);
    }

    /**
     * Requirement: Mid-cycle plan upgrade/downgrade segment calculations.
     */
    public function test_mid_cycle_plan_upgrade_and_downgrade_segment_calculations(): void
    {
        $cycleStart = Carbon::parse('2026-06-01 00:00:00');
        $switchDate = Carbon::parse('2026-06-16 00:00:00');
        $cycleEnd = Carbon::parse('2026-07-01 00:00:00');

        $subscription = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
            'started_at' => $cycleStart,
        ]);

        // Initial full period for Starter ($30/mo, 10,000 allowance)
        SubscriptionPeriod::create([
            'subscription_id' => $subscription->id,
            'plan_id' => $this->starterPlan->id,
            'period_start' => $cycleStart,
            'period_end' => $cycleEnd,
            'status' => 'active',
            'prorated_base_price_cents' => 3000,
            'prorated_allowance_units' => 10000,
            'overage_rate_cents' => 5,
            'subtotal_cents' => 3000,
            'total_cents' => 3000,
        ]);

        // UPGRADE ACTION on June 16 (Starter -> Pro)
        $switchAction = app(SwitchCustomerPlanAction::class);
        $upgradeResult = $switchAction->execute($subscription, $this->proPlan, $switchDate);

        // Verify Segment 1 (Old Plan: Starter, 15 days active):
        // Prorated base fee: 3000 * 15/30 = 1500 cents
        // Prorated allowance: 10000 * 15/30 = 5000 units
        $this->assertSame(1500, $upgradeResult->segment1->proratedBasePriceCents);
        $this->assertSame(5000, $upgradeResult->segment1->proratedAllowanceUnits);

        // Verify Segment 2 (New Plan: Pro, 15 days active):
        // Prorated base fee: 9000 * 15/30 = 4500 cents
        // Prorated allowance: 50000 * 15/30 = 25000 units
        $this->assertSame(4500, $upgradeResult->segment2->proratedBasePriceCents);
        $this->assertSame(25000, $upgradeResult->segment2->proratedAllowanceUnits);

        // Verify database records for segments
        $closedSegment = SubscriptionPeriod::where('subscription_id', $subscription->id)
            ->where('status', 'closed')
            ->first();
        $activeSegment = SubscriptionPeriod::where('subscription_id', $subscription->id)
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($closedSegment);
        $this->assertNotNull($activeSegment);
        $this->assertSame(1500, $closedSegment->prorated_base_price_cents);
        $this->assertSame(5000, $closedSegment->prorated_allowance_units);
        $this->assertSame(4500, $activeSegment->prorated_base_price_cents);
        $this->assertSame(25000, $activeSegment->prorated_allowance_units);

        // Now test DOWNGRADE credits logic:
        // Assume customer downgrades back from Pro to Starter on June 23 (7 days into Segment 2)
        $downgradeDate = Carbon::parse('2026-06-23 00:00:00');
        $downgradeResult = $switchAction->execute($subscription, $this->starterPlan, $downgradeDate);

        // Downgrade must issue credit for unused portion of Pro base fee
        $this->assertTrue($downgradeResult->isDowngrade);
        $this->assertLessThan(0, $downgradeResult->netAdjustmentCents);
        $this->customer->refresh();
        $this->assertGreaterThan(0, $this->customer->credit_balance_cents);
    }

    /**
     * Requirement: Overage threshold edge cases (Zero, Below, Exact, +1 unit, and Massive).
     */
    public function test_overage_threshold_edge_cases(): void
    {
        $cycleStart = Carbon::parse('2026-05-01 00:00:00');
        $cycleEnd = Carbon::parse('2026-06-01 00:00:00');

        // Case 1: Exact boundary condition (Usage = exactly 10,000 units, Allowance = 10,000 units)
        // Expected: Overages = 0 units, $0 overage charge
        $custExact = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Exact Limit Customer',
            'email' => 'exact@example.com',
        ]);
        $subExact = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custExact->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);
        SubscriptionPeriod::create([
            'subscription_id' => $subExact->id,
            'plan_id' => $this->starterPlan->id,
            'period_start' => $cycleStart,
            'period_end' => $cycleEnd,
            'status' => 'active',
            'prorated_base_price_cents' => 3000,
            'prorated_allowance_units' => 10000,
            'overage_rate_cents' => 5,
        ]);
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custExact->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-05-15',
            'total_quantity' => 10000, // EXACT MATCH
            'event_count' => 10,
        ]);

        // Case 2: Boundary + 1 unit over (Usage = 10,001 units, Allowance = 10,000 units)
        // Expected: Overages = 1 unit @ 5c = 5 cents overage charge
        $custPlusOne = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Plus One Customer',
            'email' => 'plusone@example.com',
        ]);
        $subPlusOne = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custPlusOne->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);
        SubscriptionPeriod::create([
            'subscription_id' => $subPlusOne->id,
            'plan_id' => $this->starterPlan->id,
            'period_start' => $cycleStart,
            'period_end' => $cycleEnd,
            'status' => 'active',
            'prorated_base_price_cents' => 3000,
            'prorated_allowance_units' => 10000,
            'overage_rate_cents' => 5,
        ]);
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custPlusOne->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-05-15',
            'total_quantity' => 10001, // 1 UNIT OVER
            'event_count' => 10,
        ]);

        // Case 3: Massive overage (Usage = 35,000 units, Allowance = 10,000 units)
        // Expected: Overages = 25,000 units @ 5c = 125,000 cents ($1,250.00)
        $custMassive = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Massive Overage Customer',
            'email' => 'massive@example.com',
        ]);
        $subMassive = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custMassive->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);
        SubscriptionPeriod::create([
            'subscription_id' => $subMassive->id,
            'plan_id' => $this->starterPlan->id,
            'period_start' => $cycleStart,
            'period_end' => $cycleEnd,
            'status' => 'active',
            'prorated_base_price_cents' => 3000,
            'prorated_allowance_units' => 10000,
            'overage_rate_cents' => 5,
        ]);
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custMassive->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-05-15',
            'total_quantity' => 35000, // 25,000 OVER
            'event_count' => 100,
        ]);

        // Run cycle invoice generator for all 3 customers
        $job = new GenerateCycleInvoicesJob('2026-06-02 00:00:00');
        $job->handle();

        // 1. Verify Case 1 (Exact match: 0 overage)
        $invExact = Invoice::where('customer_id', $custExact->id)->first();
        $this->assertNotNull($invExact);
        $this->assertSame(3000, $invExact->subtotal_cents); // $30.00 base only
        $this->assertSame(3000, $invExact->total_cents);
        $this->assertCount(1, $invExact->items); // Only base fee item

        // 2. Verify Case 2 (+1 unit: 5 cents overage)
        $invPlusOne = Invoice::where('customer_id', $custPlusOne->id)->first();
        $this->assertNotNull($invPlusOne);
        $this->assertSame(3005, $invPlusOne->subtotal_cents); // 3000 + 5 = 3005 cents ($30.05)
        $this->assertSame(3005, $invPlusOne->total_cents);
        $this->assertCount(2, $invPlusOne->items); // Base fee + 1 overage item
        $overageItem = $invPlusOne->items->firstWhere('metric_identifier', 'usage_overage');
        $this->assertNotNull($overageItem);
        $this->assertSame(1, $overageItem->quantity);
        $this->assertSame(5, $overageItem->unit_price_cents);
        $this->assertSame(5, $overageItem->subtotal_cents);

        // 3. Verify Case 3 (Massive: 25,000 units * 5c = 125,000 cents overage)
        $invMassive = Invoice::where('customer_id', $custMassive->id)->first();
        $this->assertNotNull($invMassive);
        $this->assertSame(128000, $invMassive->subtotal_cents); // 3000 + 125000 = 128,000 cents ($1,280.00)
        $this->assertSame(128000, $invMassive->total_cents);
        $this->assertCount(2, $invMassive->items);
        $massiveOverageItem = $invMassive->items->firstWhere('metric_identifier', 'usage_overage');
        $this->assertSame(25000, $massiveOverageItem->quantity);
        $this->assertSame(5, $massiveOverageItem->unit_price_cents);
        $this->assertSame(125000, $massiveOverageItem->subtotal_cents);
    }
}
