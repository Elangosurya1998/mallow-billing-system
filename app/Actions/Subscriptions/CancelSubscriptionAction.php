<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Models\Subscription;
use Illuminate\Support\Carbon;

class CancelSubscriptionAction
{
    public function execute(Subscription $subscription, bool $immediately = false): Subscription
    {
        $now = Carbon::now();

        if ($immediately) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => $now,
                'ended_at' => $now,
                'cancel_at_period_end' => false,
            ]);
        } else {
            $subscription->update([
                'canceled_at' => $now,
                'cancel_at_period_end' => true,
            ]);
        }

        return $subscription->fresh();
    }
}
