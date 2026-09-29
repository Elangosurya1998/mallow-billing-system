<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Models\Subscription;

class ResumeSubscriptionAction
{
    public function execute(Subscription $subscription): Subscription
    {
        if ($subscription->cancel_at_period_end) {
            $subscription->update([
                'cancel_at_period_end' => false,
                'canceled_at' => null,
            ]);
        }

        return $subscription->fresh();
    }
}
