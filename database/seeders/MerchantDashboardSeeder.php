<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Models\UsageEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MerchantDashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $cycleStart = $now->copy()->startOfMonth();
        $cycleEnd = $now->copy()->endOfMonth();

        // 1. Create Merchant & Active Plan
        /** @var Merchant $merchant */
        $merchant = Merchant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'email' => 'finance@acmecorp.com',
            'currency' => 'INR',
            'status' => 'active',
            'timezone' => 'Asia/Kolkata',
        ]);

        /** @var Plan $plan */
        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Growth',
            'slug' => 'growth',
            'description' => 'Growth tier plan with 250k metered allowance',
            'invoice_interval' => 'monthly',
            'base_price_cents' => 29900, // ₹299.00
            'included_units' => 250000,  // 250,000 units allowance
            'overage_unit_price_cents' => 50, // 50 cents (paise) / unit
            'is_active' => true,
        ]);

        // 2. Customers definition: Top 5, Churn-risk accounts, and additional active clients
        // Totals crafted so that current cycle sum equals EXACTLY 184,320 units
        $customerConfigs = [
            // Top 1: Beta Retail Pvt Ltd (38,200 units, ~91% of allowance)
            [
                'name' => 'Beta Retail Pvt Ltd',
                'email' => 'billing@betaretail.com',
                'cycle_units' => 38200,
                'allowance_units' => 41978, // 38,200 / 41,978 = 91%
                'prior_units' => 36000,
            ],
            // Top 2: Craft Foods Co. (31,050 units, ~78% of allowance)
            [
                'name' => 'Craft Foods Co.',
                'email' => 'accounts@craftfoods.com',
                'cycle_units' => 31050,
                'allowance_units' => 39808, // 31,050 / 39,808 = 78%
                'prior_units' => 30000,
            ],
            // Top 3: Delta Mart (27,900 units, ~70% of allowance)
            [
                'name' => 'Delta Mart',
                'email' => 'finance@deltamart.in',
                'cycle_units' => 27900,
                'allowance_units' => 39857, // 27,900 / 39,857 = 70%
                'prior_units' => 26000,
            ],
            // Top 4: Apex Logistics (24,500 units)
            [
                'name' => 'Apex Logistics',
                'email' => 'ops@apexlogistics.com',
                'cycle_units' => 24500,
                'allowance_units' => 42000,
                'prior_units' => 23000,
            ],
            // Top 5: Zenith Retailers (21,400 units)
            [
                'name' => 'Zenith Retailers',
                'email' => 'store@zenithretail.com',
                'cycle_units' => 21400,
                'allowance_units' => 42000,
                'prior_units' => 20000,
            ],
            // Moderate customer 6
            [
                'name' => 'Echo Enterprises',
                'email' => 'contact@echoenterprises.com',
                'cycle_units' => 16570,
                'allowance_units' => 40000,
                'prior_units' => 15000,
            ],
            // Moderate customer 7
            [
                'name' => 'Solaris Supplies',
                'email' => 'supply@solarissupplies.com',
                'cycle_units' => 9000,
                'allowance_units' => 40000,
                'prior_units' => 8500,
            ],
            // Churn Risk 1: Nova Traders (62% drop: 20,000 -> 7,600)
            [
                'name' => 'Nova Traders',
                'email' => 'orders@novatraders.com',
                'cycle_units' => 7600,
                'allowance_units' => 40000,
                'prior_units' => 20000, // 20000 -> 7600 = 62% drop
            ],
            // Churn Risk 2: QuickMart (55% drop: 18,000 -> 8,100)
            [
                'name' => 'QuickMart',
                'email' => 'support@quickmart.in',
                'cycle_units' => 8100,
                'allowance_units' => 40000,
                'prior_units' => 18000, // 18000 -> 8100 = 55% drop
            ],
        ];

        // 3. Create Subscriptions & Seed Usage Summaries
        // Days in current cycle that have elapsed
        $daysInCurrentCycle = max(1, (int) $cycleStart->diffInDays($now) + 1);

        $createdCustomers = [];

        foreach ($customerConfigs as $cfg) {
            /** @var Customer $customer */
            $customer = Customer::create([
                'merchant_id' => $merchant->id,
                'name' => $cfg['name'],
                'email' => $cfg['email'],
                'credit_balance_cents' => 0,
            ]);

            $createdCustomers[$cfg['name']] = $customer;

            // Create active subscription
            $subscription = Subscription::create([
                'merchant_id' => $merchant->id,
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'quantity' => 1,
            ]);

            SubscriptionPeriod::create([
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'period_start' => $cycleStart,
                'period_end' => $cycleEnd,
                'status' => 'active',
                'prorated_base_price_cents' => $plan->base_price_cents,
                'prorated_allowance_units' => $cfg['allowance_units'],
                'overage_rate_cents' => $plan->overage_unit_price_cents,
                'subtotal_cents' => $plan->base_price_cents,
                'total_cents' => $plan->base_price_cents,
            ]);

            // Distribute cycle units across the elapsed days of the current month
            $totalUnits = $cfg['cycle_units'];
            $basePerDay = (int) floor($totalUnits / $daysInCurrentCycle);
            $remainder = $totalUnits % $daysInCurrentCycle;

            for ($d = 0; $d < $daysInCurrentCycle; $d++) {
                $dateObj = $cycleStart->copy()->addDays($d);
                $unitsForDay = $basePerDay + ($d === ($daysInCurrentCycle - 1) ? $remainder : 0);

                if ($unitsForDay > 0) {
                    DailyUsageSummary::create([
                        'merchant_id' => $merchant->id,
                        'customer_id' => $customer->id,
                        'metric_identifier' => 'api_requests',
                        'usage_date' => $dateObj->toDateString(),
                        'total_quantity' => $unitsForDay,
                        'event_count' => max(1, (int) round($unitsForDay / 50)),
                        'last_aggregated_at' => $now,
                    ]);
                }
            }

            // Seed prior 30-day period usage (days 35 to 50 ago) to enable MoM churn detection
            if ($cfg['prior_units'] > 0) {
                DailyUsageSummary::create([
                    'merchant_id' => $merchant->id,
                    'customer_id' => $customer->id,
                    'metric_identifier' => 'api_requests',
                    'usage_date' => $now->copy()->subDays(42)->toDateString(),
                    'total_quantity' => $cfg['prior_units'],
                    'event_count' => max(1, (int) round($cfg['prior_units'] / 50)),
                    'last_aggregated_at' => $now->copy()->subDays(42),
                ]);
            }
        }

        // 4. Fill in any missing days in the 30-day window (before current month start)
        // so that the 30-day line chart is completely smooth and continuous without gaps
        $primaryCust = $createdCustomers['Beta Retail Pvt Ltd'];
        for ($i = 29; $i >= $daysInCurrentCycle; $i--) {
            $priorDate = $now->copy()->subDays($i)->toDateString();
            $existing = DailyUsageSummary::where('merchant_id', $merchant->id)
                ->where('usage_date', $priorDate)
                ->exists();

            if (! $existing) {
                // Realistic curve between 4,800 and 6,800 units per day
                $dailySmooth = 4800 + (($i * 127) % 2000);
                DailyUsageSummary::create([
                    'merchant_id' => $merchant->id,
                    'customer_id' => $primaryCust->id,
                    'metric_identifier' => 'api_requests',
                    'usage_date' => $priorDate,
                    'total_quantity' => $dailySmooth,
                    'event_count' => (int) round($dailySmooth / 50),
                    'last_aggregated_at' => $now->copy()->subDays($i),
                ]);
            }
        }

        // 5. Seed sample raw usage_events for recent days to demonstrate raw schema storage
        for ($e = 1; $e <= 15; $e++) {
            UsageEvent::create([
                'id' => (string) Str::uuid(),
                'merchant_id' => $merchant->id,
                'customer_id' => $primaryCust->id,
                'metric_identifier' => 'api_requests',
                'quantity' => 150 + ($e * 10),
                'idempotency_key' => sprintf('evt_acme_%s_%04d', $now->format('Ymd'), $e),
                'timestamp' => $now->copy()->subHours($e * 2),
                'properties' => [
                    'endpoint' => '/api/v1/orders',
                    'method' => 'POST',
                    'status_code' => 201,
                    'region' => 'ap-south-1',
                ],
            ]);
        }

        // 6. Seed a historical closed / paid invoice with itemized line breakdown
        $invPeriod = SubscriptionPeriod::where('subscription_id', $primaryCust->subscriptions->first()->id)->first();

        $invoice = Invoice::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $primaryCust->id,
            'subscription_id' => $primaryCust->subscriptions->first()->id,
            'subscription_period_id' => $invPeriod->id,
            'invoice_number' => 'INV-2026-0801-ACME',
            'status' => 'paid',
            'subtotal_cents' => 35900, // ₹359.00
            'tax_cents' => 0,
            'total_cents' => 35900,
            'amount_paid_cents' => 35900,
            'amount_remaining_cents' => 0,
            'due_date' => $now->copy()->subDays(20),
            'paid_at' => $now->copy()->subDays(22),
            'billing_reason' => 'subscription_cycle',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Growth Plan Base Subscription (Aug 01 - Sep 01)',
            'metric_identifier' => null,
            'quantity' => 1,
            'unit_price_cents' => 29900,
            'subtotal_cents' => 29900,
            'is_proration' => false,
            'metadata' => ['period' => 'August 2026'],
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Usage overage (12,000 units exceeding allowance)',
            'metric_identifier' => 'usage_overage',
            'quantity' => 12000,
            'unit_price_cents' => 50,
            'subtotal_cents' => 6000,
            'is_proration' => false,
            'metadata' => ['allowance' => 250000, 'actual_usage' => 262000],
        ]);

        // 6. Seed Additional Merchants for Multi-Tenant Switcher Demonstration
        $this->seedAdditionalMerchants($now);
    }

    /**
     * Seed secondary merchants (USD & EUR) to demonstrate seamless merchant switching.
     */
    private function seedAdditionalMerchants(Carbon $now): void
    {
        $cycleStart = $now->copy()->startOfMonth();
        $cycleEnd = $now->copy()->endOfMonth();

        // Merchant 2: Starlight SaaS Inc (USD)
        /** @var Merchant $starlight */
        $starlight = Merchant::create([
            'name' => 'Starlight SaaS Inc',
            'slug' => 'starlight-saas',
            'email' => 'billing@starlight.io',
            'currency' => 'USD',
            'status' => 'active',
            'timezone' => 'America/New_York',
        ]);

        /** @var Plan $starlightPlan */
        $starlightPlan = Plan::create([
            'merchant_id' => $starlight->id,
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'Enterprise volume plan with 1M metered units',
            'invoice_interval' => 'monthly',
            'base_price_cents' => 49900, // $499.00
            'included_units' => 1000000, // 1,000,000 units
            'overage_unit_price_cents' => 5, // $0.05 / unit
            'is_active' => true,
        ]);

        $starlightCustomers = [
            ['name' => 'Orbit Media Inc', 'email' => 'tech@orbitmedia.com', 'units' => 185000],
            ['name' => 'Quantum Analytics Ltd', 'email' => 'ops@quantumanalytics.com', 'units' => 145000],
            ['name' => 'Pulsar Cloud Corp', 'email' => 'infra@pulsarcloud.com', 'units' => 110000],
        ];

        foreach ($starlightCustomers as $config) {
            $cust = Customer::create([
                'merchant_id' => $starlight->id,
                'name' => $config['name'],
                'email' => $config['email'],
            ]);

            $sub = Subscription::create([
                'merchant_id' => $starlight->id,
                'customer_id' => $cust->id,
                'plan_id' => $starlightPlan->id,
                'status' => 'active',
                'quantity' => 1,
            ]);

            SubscriptionPeriod::create([
                'subscription_id' => $sub->id,
                'plan_id' => $starlightPlan->id,
                'period_start' => $cycleStart,
                'period_end' => $cycleEnd,
                'status' => 'active',
                'prorated_base_price_cents' => 49900,
                'prorated_allowance_units' => 1000000,
                'overage_rate_cents' => 5,
            ]);

            DailyUsageSummary::create([
                'merchant_id' => $starlight->id,
                'customer_id' => $cust->id,
                'metric_identifier' => 'api_requests',
                'usage_date' => $now->toDateString(),
                'total_quantity' => $config['units'],
                'event_count' => (int) round($config['units'] / 25),
            ]);
        }

        // 30-day continuous trends for Starlight
        $starlightFirstCustomer = Customer::where('merchant_id', $starlight->id)->first();
        if ($starlightFirstCustomer) {
            for ($i = 29; $i >= 1; $i--) {
                $trendDate = $now->copy()->subDays($i)->toDateString();
                DailyUsageSummary::updateOrCreate(
                    [
                        'customer_id' => $starlightFirstCustomer->id,
                        'metric_identifier' => 'api_requests',
                        'usage_date' => $trendDate,
                    ],
                    [
                        'merchant_id' => $starlight->id,
                        'total_quantity' => 12000 + (($i * 170) % 5000),
                        'event_count' => 400,
                    ]
                );
            }
        }

        // Merchant 3: Nexus Cloud Technologies (EUR)
        /** @var Merchant $nexus */
        $nexus = Merchant::create([
            'name' => 'Nexus Cloud Technologies',
            'slug' => 'nexus-cloud',
            'email' => 'finance@nexuscloud.eu',
            'currency' => 'EUR',
            'status' => 'active',
            'timezone' => 'Europe/Berlin',
        ]);

        /** @var Plan $nexusPlan */
        $nexusPlan = Plan::create([
            'merchant_id' => $nexus->id,
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Developer starter plan with 50k quota',
            'invoice_interval' => 'monthly',
            'base_price_cents' => 4900, // €49.00
            'included_units' => 50000, // 50,000 units
            'overage_unit_price_cents' => 10, // €0.10 / unit
            'is_active' => true,
        ]);

        $nexusCustomers = [
            ['name' => 'Berlin Digital Solutions', 'email' => 'dev@berlindigital.de', 'units' => 38000],
            ['name' => 'Munich Data Systems', 'email' => 'it@munichdata.de', 'units' => 32000],
        ];

        foreach ($nexusCustomers as $config) {
            $cust = Customer::create([
                'merchant_id' => $nexus->id,
                'name' => $config['name'],
                'email' => $config['email'],
            ]);

            $sub = Subscription::create([
                'merchant_id' => $nexus->id,
                'customer_id' => $cust->id,
                'plan_id' => $nexusPlan->id,
                'status' => 'active',
                'quantity' => 1,
            ]);

            SubscriptionPeriod::create([
                'subscription_id' => $sub->id,
                'plan_id' => $nexusPlan->id,
                'period_start' => $cycleStart,
                'period_end' => $cycleEnd,
                'status' => 'active',
                'prorated_base_price_cents' => 4900,
                'prorated_allowance_units' => 50000,
                'overage_rate_cents' => 10,
            ]);

            DailyUsageSummary::create([
                'merchant_id' => $nexus->id,
                'customer_id' => $cust->id,
                'metric_identifier' => 'api_requests',
                'usage_date' => $now->toDateString(),
                'total_quantity' => $config['units'],
                'event_count' => (int) round($config['units'] / 15),
            ]);
        }

        // 30-day continuous trends for Nexus
        $nexusFirstCustomer = Customer::where('merchant_id', $nexus->id)->first();
        if ($nexusFirstCustomer) {
            for ($i = 29; $i >= 1; $i--) {
                $trendDate = $now->copy()->subDays($i)->toDateString();
                DailyUsageSummary::updateOrCreate(
                    [
                        'customer_id' => $nexusFirstCustomer->id,
                        'metric_identifier' => 'api_requests',
                        'usage_date' => $trendDate,
                    ],
                    [
                        'merchant_id' => $nexus->id,
                        'total_quantity' => 2000 + (($i * 90) % 1200),
                        'event_count' => 100,
                    ]
                );
            }
        }
    }
}
