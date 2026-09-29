<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiConsoleWebViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_console_renders_successfully_with_merchants_and_customers(): void
    {
        $merchant = Merchant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'finance@acmecorp.com',
            'currency' => 'INR',
        ]);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Beta Retail Pvt Ltd',
            'email' => 'beta@example.com',
        ]);

        $response = $this->get(route('api.console'));

        $response->assertOk();
        $response->assertViewIs('console.index');
        $response->assertSee('API Web Console');
        $response->assertSee('Acme Corp');
        $response->assertSee('Beta Retail Pvt Ltd');
        $response->assertSee('Send Ingestion Request');
        $response->assertSee('/api/v1/usage');
    }

    public function test_merchant_scoped_console_renders_target_merchant(): void
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

        $cust = Customer::create([
            'merchant_id' => $m2->id,
            'name' => 'Orbit Media Inc',
            'email' => 'tech@orbitmedia.com',
        ]);

        $response = $this->get(route('merchants.console', $m2));

        $response->assertOk();
        $response->assertViewIs('console.index');
        $response->assertSee('Starlight SaaS Inc');
        $response->assertSee('Orbit Media Inc');
        $response->assertSee('USD');
    }

    public function test_console_usage_summary_endpoint_returns_json_and_200_with_tenant_header(): void
    {
        $merchant = Merchant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'finance@acmecorp.com',
            'currency' => 'INR',
        ]);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Beta Retail Pvt Ltd',
            'email' => 'beta@example.com',
        ]);

        DailyUsageSummary::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => now()->toDateString(),
            'metric_identifier' => 'api_requests',
            'total_quantity' => 150,
            'event_count' => 3,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Tenant-ID' => $merchant->id,
        ])->getJson('/api/v1/usage/summary?customer_id='.$customer->id);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['merchant_id', 'customer_id', 'usage_date', 'metric_identifier', 'total_quantity', 'event_count'],
            ],
        ]);
        $this->assertEquals(150, $response->json('data.0.total_quantity'));
    }

    public function test_console_usage_events_endpoint_returns_json_and_200_with_tenant_header(): void
    {
        $merchant = Merchant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'finance@acmecorp.com',
            'currency' => 'INR',
        ]);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Beta Retail Pvt Ltd',
            'email' => 'beta@example.com',
        ]);

        UsageEvent::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'metric_identifier' => 'api_requests',
            'quantity' => 50,
            'idempotency_key' => 'evt_console_test_1',
            'timestamp' => now(),
            'properties' => ['test' => true],
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Tenant-ID' => $merchant->id,
        ])->getJson('/api/v1/usage/events?customer_id='.$customer->id);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'merchant_id', 'customer_id', 'metric_identifier', 'quantity', 'idempotency_key'],
            ],
        ]);
        $this->assertEquals('evt_console_test_1', $response->json('data.0.idempotency_key'));
    }

    public function test_console_invoices_endpoint_returns_json_and_200_with_tenant_header(): void
    {
        $merchant = Merchant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'finance@acmecorp.com',
            'currency' => 'INR',
        ]);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Beta Retail Pvt Ltd',
            'email' => 'beta@example.com',
        ]);

        Invoice::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-TEST',
            'status' => 'paid',
            'subtotal_cents' => 3500,
            'tax_cents' => 0,
            'total_cents' => 3500,
            'amount_paid_cents' => 3500,
            'amount_remaining_cents' => 0,
            'due_date' => now()->addDays(7),
            'paid_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Tenant-ID' => $merchant->id,
        ])->getJson('/api/v1/invoices?customer_id='.$customer->id);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'merchant_id', 'customer_id', 'invoice_number', 'status', 'total_cents'],
            ],
        ]);
        $this->assertEquals('INV-2026-TEST', $response->json('data.0.invoice_number'));
    }

    public function test_console_subscription_plan_switch_returns_200_and_proration(): void
    {
        $merchant = Merchant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'finance@acmecorp.com',
            'currency' => 'USD',
        ]);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Beta Retail Pvt Ltd',
            'email' => 'beta@example.com',
        ]);

        $starterPlan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'base_price_cents' => 3000,
            'included_units' => 10000,
            'overage_unit_price_cents' => 5,
        ]);

        $proPlan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'base_price_cents' => 9000,
            'included_units' => 50000,
            'overage_unit_price_cents' => 2,
        ]);

        $sub = Subscription::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'plan_id' => $starterPlan->id,
            'status' => 'active',
            'quantity' => 1,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Tenant-ID' => $merchant->id,
        ])->patchJson('/api/v1/subscriptions/'.$sub->id, [
            'plan_id' => $proPlan->id,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'subscription' => ['id', 'merchant_id', 'plan_id', 'status'],
            'proration' => ['net_adjustment_cents', 'unused_credit_cents', 'new_plan_charge_cents'],
        ]);
        $this->assertEquals($proPlan->id, $response->json('subscription.plan_id'));
    }
}
