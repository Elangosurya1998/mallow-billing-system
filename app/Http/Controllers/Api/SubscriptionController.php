<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Subscriptions\CancelSubscriptionAction;
use App\Actions\Subscriptions\ChangeSubscriptionPlanAction;
use App\Actions\Subscriptions\CreateSubscriptionAction;
use App\Actions\Subscriptions\ResumeSubscriptionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    public function store(
        StoreSubscriptionRequest $request,
        CreateSubscriptionAction $action,
        TenantContext $tenantContext,
    ): JsonResponse {
        $tenant = $tenantContext->getTenant();
        $plan = Plan::findOrFail($request->validated('plan_id'));
        $quantity = (int) $request->validated('quantity', 1);
        $trialDays = $request->validated('trial_days');

        $subscription = $action->execute(
            tenant: $tenant,
            plan: $plan,
            quantity: $quantity,
            trialDays: $trialDays !== null ? (int) $trialDays : null,
        );

        return (new SubscriptionResource($subscription->load('plan')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Subscription $subscription): SubscriptionResource
    {
        return new SubscriptionResource($subscription->load('plan'));
    }

    public function update(
        UpdateSubscriptionRequest $request,
        Subscription $subscription,
        ChangeSubscriptionPlanAction $changePlan,
    ): JsonResponse {
        $newPlan = $request->has('plan_id')
            ? Plan::findOrFail($request->validated('plan_id'))
            : $subscription->plan;

        $newQuantity = $request->has('quantity') ? (int) $request->validated('quantity') : null;

        $result = $changePlan->execute(
            subscription: $subscription,
            newPlan: $newPlan,
            newQuantity: $newQuantity,
        );

        return response()->json([
            'subscription' => new SubscriptionResource($result['subscription']),
            'proration' => [
                'net_adjustment_cents' => $result['proration']->netAdjustmentCents,
                'unused_credit_cents' => $result['proration']->unusedCreditCents,
                'new_plan_charge_cents' => $result['proration']->newPlanChargeCents,
                'is_credit' => $result['proration']->isCredit,
                'is_debit' => $result['proration']->isDebit,
                'formatted' => $result['proration']->formattedNetAdjustment,
            ],
        ]);
    }

    public function cancel(
        Request $request,
        Subscription $subscription,
        CancelSubscriptionAction $action,
    ): SubscriptionResource {
        $immediately = $request->boolean('immediately', false);
        $canceled = $action->execute($subscription, $immediately);

        return new SubscriptionResource($canceled->load('plan'));
    }

    public function resume(
        Subscription $subscription,
        ResumeSubscriptionAction $action,
    ): SubscriptionResource {
        $resumed = $action->execute($subscription);

        return new SubscriptionResource($resumed->load('plan'));
    }
}
