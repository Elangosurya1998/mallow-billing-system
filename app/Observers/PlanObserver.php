<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Plan;
use App\Services\Plans\PlanPricingService;

class PlanObserver
{
    public function __construct(
        private readonly PlanPricingService $pricingService,
    ) {}

    public function saved(Plan $plan): void
    {
        $this->pricingService->clearCache($plan);
    }

    public function deleted(Plan $plan): void
    {
        $this->pricingService->clearCache($plan);
    }
}
