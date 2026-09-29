<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantAndCustomerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_merchant_via_api(): void
    {
        $response = $this->postJson('/api/v1/merchants', [
            'name' => 'API Registered Merchant',
            'slug' => 'api-registered-merchant',
            'email' => 'api@merchant.io',
            'currency' => 'EUR',
            'timezone' => 'Europe/Paris',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'API Registered Merchant');
        $response->assertJsonPath('data.slug', 'api-registered-merchant');
        $response->assertJsonPath('data.currency', 'EUR');

        $this->assertDatabaseHas('merchants', [
            'slug' => 'api-registered-merchant',
            'email' => 'api@merchant.io',
        ]);
    }

    public function test_can_update_merchant_via_api(): void
    {
        $merchant = Merchant::create([
            'name' => 'Initial Merchant Name',
            'slug' => 'initial-slug',
            'email' => 'init@merchant.com',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $response = $this->putJson("/api/v1/merchants/{$merchant->id}", [
            'name' => 'Updated Merchant Name',
            'slug' => 'initial-slug',
            'email' => 'updated@merchant.com',
            'currency' => 'GBP',
            'timezone' => 'Europe/London',
            'status' => 'active',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Merchant Name');
        $response->assertJsonPath('data.currency', 'GBP');

        $merchant->refresh();
        $this->assertEquals('Updated Merchant Name', $merchant->name);
        $this->assertEquals('GBP', $merchant->currency);
        $this->assertEquals('Europe/London', $merchant->timezone);
    }

    public function test_can_create_customer_via_api(): void
    {
        $merchant = Merchant::create([
            'name' => 'SaaS Provider',
            'slug' => 'saas-provider',
            'email' => 'ops@saas.com',
            'currency' => 'USD',
        ]);

        $response = $this->withHeaders(['X-Tenant-ID' => $merchant->id])
            ->postJson('/api/v1/customers', [
                'name' => 'Acme Cloud User',
                'email' => 'user@acme.org',
                'currency' => 'USD',
                'credit_balance' => 45.50,
                'external_reference' => 'EXT-1010',
                'timezone' => 'UTC',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Acme Cloud User');
        $response->assertJsonPath('data.email', 'user@acme.org');
        $response->assertJsonPath('data.credit_balance_cents', 4550);

        $this->assertDatabaseHas('customers', [
            'merchant_id' => $merchant->id,
            'email' => 'user@acme.org',
            'credit_balance_cents' => 4550,
        ]);
    }

    public function test_can_create_customer_with_subscription_via_api(): void
    {
        $merchant = Merchant::create([
            'name' => 'Metered Enterprise',
            'slug' => 'metered-enterprise',
            'email' => 'enterprise@example.com',
            'currency' => 'USD',
        ]);

        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Enterprise Pro',
            'slug' => 'enterprise-pro',
            'invoice_interval' => 'monthly',
            'base_price_cents' => 9900,
            'included_units' => 50000,
            'overage_unit_price_cents' => 5,
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['X-Tenant-ID' => $merchant->id])
            ->postJson('/api/v1/customers', [
                'name' => 'BigCorp Systems',
                'email' => 'billing@bigcorp.com',
                'plan_id' => $plan->id,
            ]);

        $response->assertCreated();

        $customer = Customer::where('email', 'billing@bigcorp.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals(1, $customer->subscriptions()->count());

        $sub = $customer->subscriptions()->first();
        $this->assertEquals($plan->id, $sub->plan_id);
        $this->assertEquals('active', $sub->status);
        $this->assertEquals(1, $sub->periods()->count());
    }

    public function test_can_list_and_get_customers_scoped_to_tenant(): void
    {
        $m1 = Merchant::create([
            'name' => 'Tenant One',
            'slug' => 'tenant-one',
            'email' => 'm1@example.com',
            'currency' => 'USD',
        ]);

        $m2 = Merchant::create([
            'name' => 'Tenant Two',
            'slug' => 'tenant-two',
            'email' => 'm2@example.com',
            'currency' => 'USD',
        ]);

        $c1 = Customer::create([
            'merchant_id' => $m1->id,
            'name' => 'Customer of M1',
            'email' => 'c1@m1.com',
            'currency' => 'USD',
            'credit_balance_cents' => 1000,
        ]);

        $c2 = Customer::create([
            'merchant_id' => $m2->id,
            'name' => 'Customer of M2',
            'email' => 'c2@m2.com',
            'currency' => 'USD',
            'credit_balance_cents' => 2000,
        ]);

        // Query M1 customers
        $response = $this->withHeaders(['X-Tenant-ID' => $m1->id])
            ->getJson('/api/v1/customers');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Customer of M1');

        // Show single customer
        $showResponse = $this->withHeaders(['X-Tenant-ID' => $m1->id])
            ->getJson("/api/v1/customers/{$c1->id}");

        $showResponse->assertOk();
        $showResponse->assertJsonPath('data.id', $c1->id);
        $showResponse->assertJsonPath('data.name', 'Customer of M1');
    }
}
