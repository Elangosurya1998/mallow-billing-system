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

class MerchantDashboardWebViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_dashboard_web_view_renders_correctly(): void
    {
        $merchant = Merchant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'finance@acmecorp.com',
            'currency' => 'INR',
        ]);

        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Growth',
            'slug' => 'growth',
            'invoice_interval' => 'monthly',
            'base_price_cents' => 99900,
            'included_units' => 250000,
            'overage_unit_price_cents' => 50,
        ]);

        $customer1 = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Beta Retail Pvt Ltd',
            'email' => 'beta@example.com',
        ]);

        $customer2 = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Craft Foods Co.',
            'email' => 'craft@example.com',
        ]);

        $sub = Subscription::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer1->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);

        SubscriptionPeriod::create([
            'subscription_id' => $sub->id,
            'plan_id' => $plan->id,
            'period_start' => Carbon::now()->startOfMonth(),
            'period_end' => Carbon::now()->endOfMonth(),
            'status' => 'active',
            'prorated_base_price_cents' => 99900,
            'prorated_allowance_units' => 250000,
            'overage_rate_cents' => 50,
        ]);

        // Seed cycle usage
        DailyUsageSummary::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer1->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => Carbon::now()->toDateString(),
            'total_quantity' => 38200,
            'event_count' => 10,
        ]);

        DailyUsageSummary::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer2->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => Carbon::now()->toDateString(),
            'total_quantity' => 31050,
            'event_count' => 8,
        ]);

        // Seed prior usage to trigger churn alert for Customer 2
        // Prior: 80,000 units, Current: 31,050 units (>50% drop)
        DailyUsageSummary::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer2->id,
            'metric_identifier' => 'api_requests',
            'usage_date' => Carbon::now()->subDays(45)->toDateString(),
            'total_quantity' => 80000,
            'event_count' => 20,
        ]);

        $response = $this->get(route('merchants.dashboard', $merchant));

        $response->assertOk();
        $response->assertViewIs('merchants.dashboard');
        $response->assertSee('Merchant Dashboard — Acme Corp', false);
        $response->assertDontSee('WIREFRAME', false);
        $response->assertSee('Current Cycle Usage');
        $response->assertSee('Projected Overage Revenue');
        $response->assertSee('Active Plan');
        $response->assertSee('Growth — monthly');
        $response->assertSee('Beta Retail Pvt Ltd');
        $response->assertSee('Craft Foods Co.');
        $response->assertSee('Churn Risk (usage ↓ >50% MoM)', false);
        $response->assertSee('Craft Foods Co. — 61% drop', false);
        $response->assertSee('System status (informational)');
    }

    public function test_merchants_list_page_renders_all_merchants_with_switch_actions(): void
    {
        $m1 = Merchant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'finance@acmecorp.com',
            'currency' => 'INR',
        ]);

        $m2 = Merchant::create([
            'name' => 'Starlight SaaS Inc',
            'slug' => 'starlight-saas',
            'email' => 'billing@starlight.io',
            'currency' => 'USD',
        ]);

        // Test /merchants
        $response = $this->get(route('merchants.index'));
        $response->assertOk();
        $response->assertViewIs('merchants.index');
        $response->assertSee('Acme Corp');
        $response->assertSee('Starlight SaaS Inc');
        $response->assertSee('Switch to Dashboard');
        $response->assertSee(route('merchants.dashboard', $m1));
        $response->assertSee(route('merchants.dashboard', $m2));

        // Test root /
        $rootResponse = $this->get('/');
        $rootResponse->assertOk();
        $rootResponse->assertSee('Acme Corp');
        $rootResponse->assertSee('Starlight SaaS Inc');
    }

    public function test_merchant_dashboard_provides_quick_switcher_for_all_merchants(): void
    {
        $m1 = Merchant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'finance@acmecorp.com',
            'currency' => 'INR',
        ]);

        $m2 = Merchant::create([
            'name' => 'Nexus Cloud Technologies',
            'slug' => 'nexus-cloud',
            'email' => 'finance@nexuscloud.eu',
            'currency' => 'EUR',
        ]);

        $response = $this->get(route('merchants.dashboard', $m1));
        $response->assertOk();
        $response->assertSee('All Merchants');
        $response->assertSee('Switch Merchant:');
        $response->assertSee(route('merchants.dashboard', $m2));
        $response->assertSee('Nexus Cloud Technologies (EUR)');
    }
}
