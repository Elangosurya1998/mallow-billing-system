<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class CustomerApiController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext): AnonymousResourceCollection
    {
        $tenantId = $tenantContext->getTenantId() ?? $request->header('X-Tenant-ID') ?? $request->input('tenant_id');

        $query = Customer::query()->where('merchant_id', $tenantId)->latest();

        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search));
        }

        $limit = min(max((int) $request->query('limit', 20), 1), 100);

        return CustomerResource::collection($query->paginate($limit));
    }

    public function store(StoreCustomerRequest $request, TenantContext $tenantContext): JsonResponse
    {
        $tenantId = $tenantContext->getTenantId() ?? $request->header('X-Tenant-ID') ?? $request->input('tenant_id');
        $merchant = Merchant::findOrFail($tenantId);

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

        if ($request->filled('plan_id')) {
            $plan = Plan::where('merchant_id', $merchant->id)->findOrFail($request->input('plan_id'));
            $start = Carbon::now();
            $end = $start->copy()->addMonth();

            $sub = Subscription::create([
                'merchant_id' => $merchant->id,
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'quantity' => 1,
            ]);

            SubscriptionPeriod::create([
                'subscription_id' => $sub->id,
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

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }
}
