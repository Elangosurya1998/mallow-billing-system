<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Phase5MerchantDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private Plan $starterPlan;

    private Plan $scalePlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'Acme Platform',
            'slug' => 'acme-platform',
            'email' => 'ops@acmeplatform.com',
            'currency' => 'USD',
        ]);

        $this->starterPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'base_price_cents' => 4900,
            'included_units' => 10000,
            'overage_unit_price_cents' => 5,
        ]);

        $this->scalePlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Scale Plan',
            'slug' => 'scale',
            'base_price_cents' => 19900,
            'included_units' => 50000,
            'overage_unit_price_cents' => 2,
        ]);
    }

    public function test_merchant_dashboard_returns_cycle_usage_vs_quota_and_projected_overage(): void
    {
        $asOf = Carbon::parse('2026-09-15 12:00:00');

        // Customer 1: Starter plan, allowance 10,000, used 15,000 -> 5,000 overage @ 5c = 25,000c ($250)
        $cust1 = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Alpha Dynamics',
            'email' => 'alpha@example.com',
        ]);
        $sub1 = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $cust1->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);
        SubscriptionPeriod::create([
            'subscription_id' => $sub1->id,
            'plan_id' => $this->starterPlan->id,
            'period_start' => Carbon::parse('2026-09-01 00:00:00'),
            'period_end' => Carbon::parse('2026-10-01 00:00:00'),
            'status' => 'active',
            'prorated_base_price_cents' => 4900,
            'prorated_allowance_units' => 10000,
            'overage_rate_cents' => 5,
        ]);
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $cust1->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-09-10',
            'total_quantity' => 15000,
            'event_count' => 100,
        ]);

        // Customer 2: Scale plan, allowance 50,000, used 20,000 -> 0 overage
        $cust2 = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Beta Systems',
            'email' => 'beta@example.com',
        ]);
        $sub2 = Subscription::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $cust2->id,
            'plan_id' => $this->scalePlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);
        SubscriptionPeriod::create([
            'subscription_id' => $sub2->id,
            'plan_id' => $this->scalePlan->id,
            'period_start' => Carbon::parse('2026-09-01 00:00:00'),
            'period_end' => Carbon::parse('2026-10-01 00:00:00'),
            'status' => 'active',
            'prorated_base_price_cents' => 19900,
            'prorated_allowance_units' => 50000,
            'overage_rate_cents' => 2,
        ]);
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $cust2->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-09-12',
            'total_quantity' => 20000,
            'event_count' => 80,
        ]);

        $response = $this->getJson("/api/v1/merchants/{$this->merchant->id}/dashboard?as_of=2026-09-15");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'merchant_id',
                'merchant_name',
                'currency',
                'as_of_date',
                'cycle_usage' => [
                    'total_usage_units',
                    'total_included_units',
                    'consumption_percentage',
                ],
                'projected_overage_revenue' => [
                    'incurred_cents',
                    'incurred_formatted',
                    'projected_cents',
                    'projected_formatted',
                ],
                'top_customers_by_usage',
                'churn_risk_customers',
            ],
        ]);

        $data = $response->json('data');

        // Total usage: 15,000 + 20,000 = 35,000 units
        // Total allowance: 10,000 + 50,000 = 60,000 units
        // Consumption percentage: (35000 / 60000) * 100 = 58.33%
        $this->assertSame(35000, $data['cycle_usage']['total_usage_units']);
        $this->assertSame(60000, $data['cycle_usage']['total_included_units']);
        $this->assertSame(58.33, $data['cycle_usage']['consumption_percentage']);

        // Incurred overage revenue: 5,000 units * 5 cents = 25,000 cents ($250.00)
        $this->assertSame(25000, $data['projected_overage_revenue']['incurred_cents']);
        $this->assertSame('USD 250.00', $data['projected_overage_revenue']['incurred_formatted']);
    }

    public function test_merchant_dashboard_ranks_top_5_customers_by_allowance_consumption(): void
    {
        // Create 6 customers with varying consumption %
        for ($i = 1; $i <= 6; $i++) {
            $cust = Customer::create([
                'merchant_id' => $this->merchant->id,
                'name' => "Customer {$i}",
                'email' => "cust{$i}@example.com",
            ]);

            $sub = Subscription::create([
                'merchant_id' => $this->merchant->id,
                'customer_id' => $cust->id,
                'plan_id' => $this->starterPlan->id,
                'status' => 'active',
                'quantity' => 1,
            ]);

            SubscriptionPeriod::create([
                'subscription_id' => $sub->id,
                'plan_id' => $this->starterPlan->id,
                'period_start' => Carbon::parse('2026-09-01 00:00:00'),
                'period_end' => Carbon::parse('2026-10-01 00:00:00'),
                'status' => 'active',
                'prorated_base_price_cents' => 4900,
                'prorated_allowance_units' => 10000,
                'overage_rate_cents' => 5,
            ]);

            // Usage percentages:
            // Cust 1: 2000 (20%)
            // Cust 2: 4000 (40%)
            // Cust 3: 6000 (60%)
            // Cust 4: 8000 (80%)
            // Cust 5: 12000 (120%)
            // Cust 6: 15000 (150%)
            DailyUsageSummary::create([
                'merchant_id' => $this->merchant->id,
                'customer_id' => $cust->id,
                'metric_identifier' => 'api_requests',
                'usage_date' => '2026-09-10',
                'total_quantity' => $i === 5 ? 12000 : ($i === 6 ? 15000 : $i * 2000),
                'event_count' => 10,
            ]);
        }

        $response = $this->getJson("/api/v1/merchants/{$this->merchant->id}/dashboard?as_of=2026-09-15");
        $response->assertOk();

        $top = $response->json('data.top_customers_by_usage');

        // Should return exactly top 5 (out of 6 total)
        $this->assertCount(5, $top);

        // Highest consumption should be Cust 6 (150%)
        $this->assertSame('Customer 6', $top[0]['customer_name']);
        $this->assertEquals(150.0, $top[0]['percentage_consumed']);

        // Second should be Cust 5 (120%)
        $this->assertSame('Customer 5', $top[1]['customer_name']);
        $this->assertEquals(120.0, $top[1]['percentage_consumed']);

        // Third should be Cust 4 (80%)
        $this->assertSame('Customer 4', $top[2]['customer_name']);
        $this->assertEquals(80.0, $top[2]['percentage_consumed']);
    }

    public function test_merchant_dashboard_identifies_churn_risk_customers_with_greater_than_50_percent_drop(): void
    {
        $asOf = Carbon::parse('2026-09-15');

        // Customer A: Previous 30-day window (July 17 - Aug 15) = 20,000 units
        //             Current 30-day window (Aug 16 - Sep 15) = 4,000 units
        //             Drop = 80.0% (>50% -> CHURN RISK)
        $custRisk = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Fading Star Inc',
            'email' => 'contact@fadingstar.com',
        ]);
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custRisk->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-08-01',
            'total_quantity' => 20000,
            'event_count' => 50,
        ]);
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custRisk->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-09-01',
            'total_quantity' => 4000,
            'event_count' => 10,
        ]);

        // Customer B: Previous = 10,000 units, Current = 8,000 units
        //             Drop = 20.0% (<=50% -> NOT churn risk)
        $custSafe = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Steady Growth LLC',
            'email' => 'ops@steadygrowth.io',
        ]);
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custSafe->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-08-01',
            'total_quantity' => 10000,
            'event_count' => 30,
        ]);
        DailyUsageSummary::create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $custSafe->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => '2026-09-01',
            'total_quantity' => 8000,
            'event_count' => 25,
        ]);

        $response = $this->getJson("/api/v1/merchants/{$this->merchant->id}/dashboard?as_of=2026-09-15");
        $response->assertOk();

        $churnRisk = $response->json('data.churn_risk_customers');

        $this->assertCount(1, $churnRisk);
        $this->assertSame($custRisk->id, $churnRisk[0]['customer_id']);
        $this->assertSame('Fading Star Inc', $churnRisk[0]['customer_name']);
        $this->assertSame(20000, $churnRisk[0]['previous_period_usage']);
        $this->assertSame(4000, $churnRisk[0]['current_period_usage']);
        $this->assertEquals(80.0, $churnRisk[0]['drop_percentage']);
        $this->assertSame('high', $churnRisk[0]['risk_level']);
    }
}
