<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\UsageEvent;

final class RecordUsageResultDto
{
    public private(set) UsageEvent $event;

    public private(set) bool $isDuplicate;

    public function __construct(
        UsageEvent $event,
        bool $isDuplicate = false,
    ) {
        $this->event = $event;
        $this->isDuplicate = $isDuplicate;
    }

    public int $httpStatusCode {
        get => $this->isDuplicate ? 200 : 201;
    }
}
