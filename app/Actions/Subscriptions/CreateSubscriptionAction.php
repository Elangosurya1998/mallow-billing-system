<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateSubscriptionAction
{
    public function execute(
        Tenant $tenant,
        Plan $plan,
        int $quantity = 1,
        ?int $trialDays = null,
    ): Subscription {
        return DB::transaction(function () use ($tenant, $plan, $quantity, $trialDays): Subscription {
            $now = Carbon::now();
            $trialPeriodDays = $trialDays ?? $plan->trial_period_days;
            $hasTrial = $trialPeriodDays > 0;

            $periodStart = $now;
            $periodEnd = $plan->invoice_interval === 'year'
                ? $now->copy()->addYear()
                : $now->copy()->addMonth();

            $trialEndsAt = $hasTrial ? $now->copy()->addDays($trialPeriodDays) : null;
            $status = $hasTrial ? 'trialing' : 'active';

            return Subscription::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'quantity' => $quantity,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'trial_ends_at' => $trialEndsAt,
                'cancel_at_period_end' => false,
            ]);
        });
    }
}
