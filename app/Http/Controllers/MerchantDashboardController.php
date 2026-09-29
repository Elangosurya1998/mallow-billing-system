<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreMerchantRequest;
use App\Http\Requests\UpdateMerchantRequest;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Services\MerchantDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MerchantDashboardController extends Controller
{
    /**
     * Display a listing of all registered merchants with direct dashboard switch options.
     */
    public function index(): View
    {
        $merchants = Merchant::query()
            ->with(['plans' => fn ($query) => $query->where('is_active', true)])
            ->withCount(['customers', 'plans'])
            ->orderBy('name')
            ->get();

        return view('merchants.index', compact('merchants'));
    }

    /**
     * Show form to create a new merchant.
     */
    public function create(): View
    {
        return view('merchants.create');
    }

    /**
     * Store a newly created merchant in storage.
     */
    public function store(StoreMerchantRequest $request): RedirectResponse
    {
        $name = (string) $request->validated('name');
        $slug = (string) ($request->validated('slug') ?: Str::slug($name));

        $baseSlug = $slug;
        $counter = 1;
        while (Merchant::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $merchant = Merchant::create([
            'name' => $name,
            'slug' => $slug,
            'email' => (string) $request->validated('email'),
            'currency' => strtoupper((string) $request->validated('currency', 'USD')),
            'timezone' => (string) $request->validated('timezone', 'UTC'),
            'status' => (string) $request->validated('status', 'active'),
        ]);

        // Seed default starter and growth plans for immediate billing readiness
        Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Starter Plan',
            'slug' => 'starter-'.Str::lower(Str::random(4)),
            'description' => 'Starter tier with 10,000 included usage units',
            'invoice_interval' => 'monthly',
            'base_price_cents' => 2900,
            'included_units' => 10000,
            'overage_unit_price_cents' => 5,
            'is_active' => true,
        ]);

        Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Growth Plan',
            'slug' => 'growth-'.Str::lower(Str::random(4)),
            'description' => 'Growth volume tier with 50,000 included usage units',
            'invoice_interval' => 'monthly',
            'base_price_cents' => 7900,
            'included_units' => 50000,
            'overage_unit_price_cents' => 3,
            'is_active' => true,
        ]);

        return redirect()
            ->route('merchants.dashboard', $merchant)
            ->with('success', "Merchant \"{$merchant->name}\" created successfully with initial billing plans!");
    }

    /**
     * Display the visual merchant dashboard view.
     */
    public function show(Merchant $merchant, MerchantDashboardService $service): View
    {
        $metrics = $service->getDashboardMetrics($merchant);
        $allMerchants = Merchant::query()
            ->select(['id', 'name', 'slug', 'currency'])
            ->orderBy('name')
            ->get();

        $merchant->load(['customers' => fn ($q) => $q->latest()->limit(10), 'plans']);

        return view('merchants.dashboard', array_merge($metrics, [
            'current_merchant' => $merchant,
            'all_merchants' => $allMerchants,
        ]));
    }

    /**
     * Show form to edit an existing merchant.
     */
    public function edit(Merchant $merchant): View
    {
        return view('merchants.edit', compact('merchant'));
    }

    /**
     * Update the specified merchant in storage.
     */
    public function update(UpdateMerchantRequest $request, Merchant $merchant): RedirectResponse
    {
        $merchant->update([
            'name' => (string) $request->validated('name'),
            'slug' => (string) $request->validated('slug'),
            'email' => (string) $request->validated('email'),
            'currency' => strtoupper((string) $request->validated('currency')),
            'timezone' => (string) $request->validated('timezone'),
            'status' => (string) $request->validated('status'),
        ]);

        return redirect()
            ->route('merchants.dashboard', $merchant)
            ->with('success', "Merchant \"{$merchant->name}\" updated successfully!");
    }

    /**
     * Show form to add a new customer under a merchant.
     */
    public function createCustomer(Merchant $merchant): View
    {
        $plans = $merchant->plans()->where('is_active', true)->get();

        return view('merchants.customers.create', compact('merchant', 'plans'));
    }

    /**
     * Store a new customer under a merchant with optional initial subscription.
     */
    public function storeCustomer(StoreCustomerRequest $request, Merchant $merchant): RedirectResponse
    {
        $creditBalanceDollars = (float) $request->input('credit_balance', 0);
        $creditBalanceCents = (int) round($creditBalanceDollars * 100);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => (string) $request->validated('name'),
            'email' => (string) $request->validated('email'),
            'currency' => strtoupper((string) $request->input('currency', $merchant->currency)),
            'credit_balance_cents' => max(0, $creditBalanceCents),
            'external_reference' => $request->input('external_reference'),
            'timezone' => (string) $request->input('timezone', $merchant->timezone),
        ]);

        // If a plan is selected, enroll the customer in a subscription cycle immediately
        if ($request->filled('plan_id')) {
            $plan = Plan::where('merchant_id', $merchant->id)->findOrFail($request->input('plan_id'));
            $start = Carbon::now();
            $end = $start->copy()->addMonth();

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
                'period_start' => $start,
                'period_end' => $end,
                'status' => 'active',
                'subtotal_cents' => $plan->base_price_cents,
                'total_cents' => $plan->base_price_cents,
                'prorated_base_price_cents' => $plan->base_price_cents,
                'prorated_allowance_units' => $plan->included_units,
                'overage_rate_cents' => $plan->overage_unit_price_cents,
            ]);
        }

        return redirect()
            ->route('merchants.dashboard', $merchant)
            ->with('success', "Customer \"{$customer->name}\" added successfully to {$merchant->name}!");
    }
}
