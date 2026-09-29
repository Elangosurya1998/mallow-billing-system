<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Subscription;
use App\Models\SubscriptionPeriod;

final class PlanSwitchResultDto
{
    public private(set) Subscription $subscription;

    public private(set) PlanSegmentDto $segment1;

    public private(set) PlanSegmentDto $segment2;

    public private(set) int $unusedCreditCents;

    public private(set) int $newChargeCents;

    public private(set) int $netAdjustmentCents;

    public private(set) SubscriptionPeriod $periodSegment1;

    public private(set) SubscriptionPeriod $periodSegment2;

    public function __construct(
        Subscription $subscription,
        PlanSegmentDto $segment1,
        PlanSegmentDto $segment2,
        int $unusedCreditCents,
        int $newChargeCents,
        int $netAdjustmentCents,
        SubscriptionPeriod $periodSegment1,
        SubscriptionPeriod $periodSegment2,
    ) {
        $this->subscription = $subscription;
        $this->segment1 = $segment1;
        $this->segment2 = $segment2;
        $this->unusedCreditCents = $unusedCreditCents;
        $this->newChargeCents = $newChargeCents;
        $this->netAdjustmentCents = $netAdjustmentCents;
        $this->periodSegment1 = $periodSegment1;
        $this->periodSegment2 = $periodSegment2;
    }

    public bool $isUpgrade {
        get => $this->netAdjustmentCents > 0;
    }

    public bool $isDowngrade {
        get => $this->netAdjustmentCents < 0;
    }

    public string $formattedNetAdjustment {
        get => sprintf(
            '%s$%.2f',
            $this->netAdjustmentCents < 0 ? '-' : '',
            abs($this->netAdjustmentCents) / 100
        );
    }
}
