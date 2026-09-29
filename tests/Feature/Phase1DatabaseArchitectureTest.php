<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Models\UsageEvent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Phase1DatabaseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private Plan $plan;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'Apex Cloud Inc',
            'slug' => 'apex-cloud',
            'email' => 'billing@apexcloud.com',
            'currency' => 'USD',
            'status' => 'active',
            'timezone' => 'America/New_York',
        ]);

        $this->plan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Scale Tier',
            'slug' => 'scale-tier',
            'description' => 'Dedicated infrastructure with high-volume metering',
            'invoice_interval' => 'month',
            'base_price_cents' => 19900, // $199.00
            'trial_period_days' => 14,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Cyberdyne Systems',
            'email' => 'accounts@cyberdyne.io',
            'currency' => 'USD',
            'credit_balance_cents' => 5000, // $50.00 credit
            'external_reference' => 'EXT-CUST-883',
            'timezone' => 'UTC',
        ]);
    }

    public function test_merchant_customer_plan_relationships(): void
    {
        $this->assertInstanceOf(Merchant::class, $this->customer->merchant);
        $this->assertSame($this->merchant->id, $this->customer->merchant->id);
        $this->assertCount(1, $this->merchant->customers);
        $this->assertCount(1, $this->merchant->plans);
        $this->assertSame(19900, $this->plan->base_price_cents);
        $this->assertSame(5000, $this->customer->credit_balance_cents);
    }

    public function test_subscription_and_subscription_period_lifecycle(): void
    {
        $subscription = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'quantity' => 3,
            'trial_ends_at' => null,
            'cancel_at_period_end' => false,
        ]);

        $start = Carbon::parse('2026-09-01 00:00:00');
        $end = Carbon::parse('2026-10-01 00:00:00');

        $period = SubscriptionPeriod::create([
            'subscription_id' => $subscription->id,
            'period_start' => $start,
            'period_end' => $end,
            'status' => 'active',
            'subtotal_cents' => 59700, // 3 seats * 19900 = 59700 cents ($597.00)
            'total_cents' => 59700,
        ]);

        $this->assertTrue($subscription->isActive());
        $this->assertFalse($subscription->isCanceled());
        $this->assertCount(1, $subscription->periods);
        $this->assertSame($subscription->id, $period->subscription->id);
        $this->assertSame(59700, $period->total_cents);
        $this->assertEquals($period->id, $subscription->currentPeriod()->id);
    }

    public function test_usage_events_unique_idempotency_constraint(): void
    {
        $now = Carbon::parse('2026-09-27 12:00:00');

        $event1 = UsageEvent::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_requests',
            'quantity' => 100,
            'idempotency_key' => 'idemp-tx-1001',
            'timestamp' => $now,
            'properties' => ['region' => 'us-east-1'],
        ]);

        $this->assertNotNull($event1->id);
        $this->assertSame(100, $event1->quantity);

        // Attempt duplicate insert with same customer_id and idempotency_key
        $this->expectException(QueryException::class);

        UsageEvent::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_requests',
            'quantity' => 100,
            'idempotency_key' => 'idemp-tx-1001',
            'timestamp' => $now,
        ]);
    }

    public function test_daily_usage_summaries_unique_day_constraint(): void
    {
        $summary = DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'gpu_seconds',
            'usage_date' => '2026-09-27',
            'total_quantity' => 3600,
            'event_count' => 12,
        ]);

        $this->assertSame(3600, $summary->total_quantity);
        $this->assertSame(12, $summary->event_count);

        // Attempt duplicate daily aggregate entry for same customer, metric, date
        $this->expectException(QueryException::class);

        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'gpu_seconds',
            'usage_date' => '2026-09-27',
            'total_quantity' => 1800,
            'event_count' => 6,
        ]);
    }

    public function test_invoices_and_invoice_items_with_integer_cents(): void
    {
        $subscription = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);

        $period = SubscriptionPeriod::create([
            'subscription_id' => $subscription->id,
            'period_start' => Carbon::now()->subMonth(),
            'period_end' => Carbon::now(),
            'status' => 'billed',
            'subtotal_cents' => 19900,
            'total_cents' => 19900,
        ]);

        $invoice = Invoice::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $this->customer->id,
            'subscription_id' => $subscription->id,
            'subscription_period_id' => $period->id,
            'invoice_number' => 'INV-2026-9901',
            'status' => 'open',
            'subtotal_cents' => 24900, // $249.00
            'tax_cents' => 0,
            'total_cents' => 24900,
            'amount_paid_cents' => 0,
            'amount_remaining_cents' => 24900,
            'due_date' => Carbon::now()->addDays(7),
            'billing_reason' => 'subscription_cycle',
        ]);

        $baseItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Scale Tier (1 seat)',
            'metric_identifier' => null,
            'quantity' => 1,
            'unit_price_cents' => 19900,
            'subtotal_cents' => 19900,
            'is_proration' => false,
        ]);

        $overageItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Overage for compute_seconds (50,000 units)',
            'metric_identifier' => 'compute_seconds',
            'quantity' => 50000,
            'unit_price_cents' => 1,
            'subtotal_cents' => 5000, // 5000 cents ($50.00)
            'is_proration' => false,
        ]);

        $this->assertCount(2, $invoice->items);
        $this->assertSame(24900, $invoice->total_cents);
        $this->assertSame($this->customer->id, $invoice->customer->id);
        $this->assertSame($period->id, $invoice->period->id);
        $this->assertTrue($invoice->isOpen());
        $this->assertFalse($invoice->isPaid());
    }
}
