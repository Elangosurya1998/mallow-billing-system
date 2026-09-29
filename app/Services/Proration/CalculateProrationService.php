<?php

declare(strict_types=1);

namespace App\Services\Proration;

use App\DTOs\ProrationResultDto;
use App\Models\Plan;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CalculateProrationService
{
    /**
     * Compute mathematical second-accurate proration between plans.
     */
    public function calculate(
        Plan $currentPlan,
        Plan $newPlan,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        ?CarbonInterface $prorationAt = null,
        int $quantity = 1,
    ): ProrationResultDto {
        $prorationAt = $prorationAt ?? Carbon::now();

        $startTimestamp = $periodStart->getTimestamp();
        $endTimestamp = $periodEnd->getTimestamp();
        $totalSeconds = max(1, $endTimestamp - $startTimestamp);

        // Clamp the switch timestamp to within the period boundaries
        $switchTimestamp = min($endTimestamp, max($startTimestamp, $prorationAt->getTimestamp()));
        $remainingSeconds = max(0, $endTimestamp - $switchTimestamp);

        $ratio = $remainingSeconds / $totalSeconds;

        $currentPlanBase = $currentPlan->base_price_cents * $quantity;
        $newPlanBase = $newPlan->base_price_cents * $quantity;

        // Mathematical rounding to integer cents (PHP_ROUND_HALF_UP avoids floating point bias)
        $unusedCreditCents = (int) round($currentPlanBase * $ratio, 0, PHP_ROUND_HALF_UP);
        $newPlanChargeCents = (int) round($newPlanBase * $ratio, 0, PHP_ROUND_HALF_UP);

        $netAdjustmentCents = $newPlanChargeCents - $unusedCreditCents;

        return new ProrationResultDto(
            unusedCreditCents: $unusedCreditCents,
            newPlanChargeCents: $newPlanChargeCents,
            netAdjustmentCents: $netAdjustmentCents,
            totalSecondsInPeriod: $totalSeconds,
            remainingSeconds: $remainingSeconds,
            remainingRatio: $ratio,
        );
    }
}
