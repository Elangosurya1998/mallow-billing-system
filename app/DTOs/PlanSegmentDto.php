<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Plan;
use Carbon\CarbonInterface;

final class PlanSegmentDto
{
    public private(set) int $segmentNumber;

    public private(set) Plan $plan;

    public private(set) CarbonInterface $start;

    public private(set) CarbonInterface $end;

    public private(set) int $durationSeconds;

    public private(set) float $ratio;

    public private(set) int $proratedBasePriceCents;

    public private(set) int $proratedAllowanceUnits;

    public private(set) int $overageUnitPriceCents;

    public function __construct(
        int $segmentNumber,
        Plan $plan,
        CarbonInterface $start,
        CarbonInterface $end,
        int $durationSeconds,
        float $ratio,
        int $proratedBasePriceCents,
        int $proratedAllowanceUnits,
        int $overageUnitPriceCents,
    ) {
        $this->segmentNumber = $segmentNumber;
        $this->plan = $plan;
        $this->start = $start;
        $this->end = $end;
        $this->durationSeconds = $durationSeconds;
        $this->ratio = $ratio;
        $this->proratedBasePriceCents = $proratedBasePriceCents;
        $this->proratedAllowanceUnits = $proratedAllowanceUnits;
        $this->overageUnitPriceCents = $overageUnitPriceCents;
    }

    public string $formattedProratedBasePrice {
        get => sprintf('$%.2f', $this->proratedBasePriceCents / 100);
    }
}
