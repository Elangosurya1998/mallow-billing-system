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

    public function test_merchant_create_page_renders_and_merchant_is_created_with_plans(): void
    {
        $response = $this->get(route('merchants.create'));
        $response->assertOk();
        $response->assertViewIs('merchants.create');
        $response->assertSee('Register New Merchant');

        $postResponse = $this->post(route('merchants.store'), [
            'name' => 'Zenith Global Inc',
            'email' => 'admin@zenithglobal.com',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $merchant = Merchant::where('email', 'admin@zenithglobal.com')->first();
        $this->assertNotNull($merchant);
        $this->assertEquals('Zenith Global Inc', $merchant->name);
        $this->assertEquals('zenith-global-inc', $merchant->slug);
        $this->assertEquals(2, $merchant->plans()->count());

        $postResponse->assertRedirect(route('merchants.dashboard', $merchant));
        $postResponse->assertSessionHas('success');
    }

    public function test_merchant_edit_page_renders_and_merchant_is_updated(): void
    {
        $merchant = Merchant::create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
            'email' => 'old@example.com',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $response = $this->get(route('merchants.edit', $merchant));
        $response->assertOk();
        $response->assertViewIs('merchants.edit');
        $response->assertSee('Original Name');

        $putResponse = $this->put(route('merchants.update', $merchant), [
            'name' => 'Renamed Corporation',
            'slug' => 'original-slug',
            'email' => 'updated@example.com',
            'currency' => 'EUR',
            'timezone' => 'Europe/Berlin',
            'status' => 'active',
        ]);

        $merchant->refresh();
        $this->assertEquals('Renamed Corporation', $merchant->name);
        $this->assertEquals('EUR', $merchant->currency);
        $this->assertEquals('Europe/Berlin', $merchant->timezone);

        $putResponse->assertRedirect(route('merchants.dashboard', $merchant));
        $putResponse->assertSessionHas('success');
    }

    public function test_customer_create_page_renders_and_customer_is_enrolled_with_subscription(): void
    {
        $merchant = Merchant::create([
            'name' => 'Cloud Provider Corp',
            'slug' => 'cloud-provider',
            'email' => 'ops@cloudprovider.com',
            'currency' => 'USD',
        ]);

        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Standard Tier',
            'slug' => 'standard',
            'invoice_interval' => 'monthly',
            'base_price_cents' => 4900,
            'included_units' => 20000,
            'overage_unit_price_cents' => 4,
            'is_active' => true,
        ]);

        $response = $this->get(route('merchants.customers.create', $merchant));
        $response->assertOk();
        $response->assertViewIs('merchants.customers.create');
        $response->assertSee('Standard Tier');

        $postResponse = $this->post(route('merchants.customers.store', $merchant), [
            'name' => 'HyperScale Software',
            'email' => 'finance@hyperscale.io',
            'currency' => 'USD',
            'credit_balance' => '25.50',
            'external_reference' => 'REF-9921',
            'timezone' => 'UTC',
            'plan_id' => $plan->id,
        ]);

        $customer = Customer::where('email', 'finance@hyperscale.io')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('HyperScale Software', $customer->name);
        $this->assertEquals(2550, $customer->credit_balance_cents);
        $this->assertEquals($merchant->id, $customer->merchant_id);

        // Assert subscription and period were automatically created
        $subscription = $customer->subscriptions()->first();
        $this->assertNotNull($subscription);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals(1, $subscription->periods()->count());

        $postResponse->assertRedirect(route('merchants.dashboard', $merchant));
        $postResponse->assertSessionHas('success');
    }
}
