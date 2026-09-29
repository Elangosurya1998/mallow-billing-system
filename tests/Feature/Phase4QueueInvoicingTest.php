<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Subscriptions\SwitchCustomerPlanAction;
use App\Jobs\AggregateDailyUsageJob;
use App\Jobs\GenerateCycleInvoicesJob;
use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Models\UsageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Phase4QueueInvoicingTest extends TestCase
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
            'name' => 'Nexus Cloud',
            'slug' => 'nexus-cloud',
            'email' => 'billing@nexus.com',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Starlight Media',
            'email' => 'finance@starlight.io',
            'credit_balance_cents' => 0,
        ]);

        // Starter: $30/mo, 10,000 allowance, 5 cents overage
        $this->starterPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'base_price_cents' => 3000,
            'included_units' => 10000,
            'overage_unit_price_cents' => 5,
        ]);

        // Pro: $90/mo, 50,000 allowance, 2 cents overage
        $this->proPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'base_price_cents' => 9000,
            'included_units' => 50000,
            'overage_unit_price_cents' => 2,
        ]);
    }

    public function test_aggregate_daily_usage_job_chunks_and_rolls_up_events(): void
    {
        $date = '2026-09-27';

        // Seed 10 raw events directly
        for ($i = 1; $i <= 10; $i++) {
            UsageEvent::create([
                'merchant_id' => $this->merchant->id,
                'customer_id' => $this->customer->id,
                'metric_identifier' => 'api_requests',
                'quantity' => 100,
                'idempotency_key' => "raw-evt-{$i}",
                'timestamp' => Carbon::parse("{$date} 10:00:00"),
            ]);
        }

        $this->assertDatabaseMissing('daily_usage_summaries', [
            'customer_id' => $this->customer->id,
            'usage_date' => $date,
        ]);

        // Dispatch aggregation job
        $job = new AggregateDailyUsageJob($date, $this->customer->id);
        $job->handle();

        // Verify summary was created with total = 10 * 100 = 1000 units
        $summary = DailyUsageSummary::where('customer_id', $this->customer->id)
            ->where('metric_identifier', 'api_requests')
            ->where('usage_date', $date)
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame(1000, $summary->total_quantity);
        $this->assertSame(10, $summary->event_count);
    }

    public function test_generate_cycle_invoices_job_computes_overages_and_creates_invoice(): void
    {
        $subscription = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);

        $start = Carbon::parse('2026-08-01 00:00:00');
        $end = Carbon::parse('2026-09-01 00:00:00');

        $period = SubscriptionPeriod::create([
            'subscription_id' => $subscription->id,
            'plan_id' => $this->starterPlan->id,
            'period_start' => $start,
            'period_end' => $end,
            'status' => 'active',
            'prorated_base_price_cents' => 3000, // $30.00
            'prorated_allowance_units' => 10000, // 10,000 units
            'overage_rate_cents' => 5,           // 5 cents/unit overage
            'subtotal_cents' => 3000,
            'total_cents' => 3000,
        ]);

        // Pre-aggregate 14,000 units in daily_usage_summaries during August
        // Over allowance by 4,000 units -> 4,000 * 5 cents = 20,000 cents ($200.00)
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-08-15',
            'total_quantity' => 14000,
            'event_count' => 50,
        ]);

        // Run cycle invoice generator as of September 2
        $job = new GenerateCycleInvoicesJob('2026-09-02 00:00:00');
        $job->handle();

        // Verify invoice was generated
        $invoice = Invoice::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame('open', $invoice->status);

        // Expected Cost:
        // Base fee: $30.00 = 3000 cents
        // Overage: 4,000 units * 5c = 20,000 cents
        // Total = 23,000 cents ($230.00)
        $this->assertSame(23000, $invoice->subtotal_cents);
        $this->assertSame(23000, $invoice->total_cents);
        $this->assertSame(23000, $invoice->amount_remaining_cents);

        // Verify invoice items: 1 base fee item + 1 overage item
        $this->assertCount(2, $invoice->items);

        // Verify period marked as billed
        $period->refresh();
        $this->assertSame('billed', $period->status);

        // Verify next cycle active period was opened
        $nextPeriod = $subscription->periods()->where('status', 'active')->first();
        $this->assertNotNull($nextPeriod);
        $this->assertEquals($end->toDateTimeString(), $nextPeriod->period_start->toDateTimeString());
    }

    public function test_generate_cycle_invoices_job_handles_segmented_mid_cycle_switch(): void
    {
        $subscription = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);

        $start = Carbon::parse('2026-06-01 00:00:00');
        $end = Carbon::parse('2026-07-01 00:00:00');

        SubscriptionPeriod::create([
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

        // Mid-cycle switch on June 16 (Starter -> Pro)
        $switchAction = app(SwitchCustomerPlanAction::class);
        $switchAction->execute($subscription, $this->proPlan, Carbon::parse('2026-06-16 00:00:00'));

        // Seed usage:
        // Segment 1 (June 1 - June 16): 6,000 units (allowance 5,000) -> 1,000 overage @ 5c = 5,000c ($50.00)
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-06-10',
            'total_quantity' => 6000,
            'event_count' => 20,
        ]);

        // Segment 2 (June 16 - July 1): 20,000 units (allowance 25,000) -> 0 overage
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-06-25',
            'total_quantity' => 20000,
            'event_count' => 60,
        ]);

        // Run cycle invoice generator as of July 2
        $job = new GenerateCycleInvoicesJob('2026-07-02 00:00:00');
        $job->handle();

        $invoice = Invoice::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($invoice);

        // Expected Breakdown:
        // Segment 1 Base Fee: 1500 cents ($15.00)
        // Segment 1 Overage: 1,000 units * 5c = 5000 cents ($50.00)
        // Segment 2 Base Fee: 4500 cents ($45.00)
        // Segment 2 Overage: 0 cents
        // Total = 1500 + 5000 + 4500 = 11,000 cents ($110.00)
        $this->assertSame(11000, $invoice->subtotal_cents);
        $this->assertSame(11000, $invoice->total_cents);
        $this->assertCount(3, $invoice->items); // 2 base fee items + 1 overage item
    }
}
