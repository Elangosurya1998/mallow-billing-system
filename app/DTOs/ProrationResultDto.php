<?php

declare(strict_types=1);

namespace App\DTOs;

final class ProrationResultDto
{
    public private(set) int $unusedCreditCents;

    public private(set) int $newPlanChargeCents;

    public private(set) int $netAdjustmentCents;

    public private(set) int $totalSecondsInPeriod;

    public private(set) int $remainingSeconds;

    public private(set) float $remainingRatio;

    public function __construct(
        int $unusedCreditCents,
        int $newPlanChargeCents,
        int $netAdjustmentCents,
        int $totalSecondsInPeriod,
        int $remainingSeconds,
        float $remainingRatio,
    ) {
        $this->unusedCreditCents = $unusedCreditCents;
        $this->newPlanChargeCents = $newPlanChargeCents;
        $this->netAdjustmentCents = $netAdjustmentCents;
        $this->totalSecondsInPeriod = $totalSecondsInPeriod;
        $this->remainingSeconds = $remainingSeconds;
        $this->remainingRatio = $remainingRatio;
    }

    public bool $isCredit {
        get => $this->netAdjustmentCents < 0;
    }

    public bool $isDebit {
        get => $this->netAdjustmentCents > 0;
    }

    public string $formattedNetAdjustment {
        get => sprintf(
            '%s$%.2f',
            $this->netAdjustmentCents < 0 ? '-' : '',
            abs($this->netAdjustmentCents) / 100
        );
    }
}
