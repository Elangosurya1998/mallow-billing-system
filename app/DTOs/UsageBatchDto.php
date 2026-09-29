<?php

declare(strict_types=1);

namespace App\DTOs;

final class UsageBatchDto
{
    public private(set) string $tenantId;

    /**
     * @var array<int, UsageEventDto>
     */
    public private(set) array $events;

    /**
     * @param  array<int, UsageEventDto>  $events
     */
    public function __construct(
        string $tenantId,
        array $events,
    ) {
        $this->tenantId = $tenantId;
        $this->events = $events;
    }

    public int $count {
        get => count($this->events);
    }

    public int $totalQuantity {
        get => array_sum(array_map(fn (UsageEventDto $e): int => $e->quantity, $this->events));
    }

    public static function fromArray(array $eventsData, string $tenantId): self
    {
        $events = [];
        foreach ($eventsData as $item) {
            $events[] = $item instanceof UsageEventDto
                ? $item
                : UsageEventDto::fromArray($item, $tenantId);
        }

        return new self($tenantId, $events);
    }
}
