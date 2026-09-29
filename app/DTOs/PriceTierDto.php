<?php

declare(strict_types=1);

namespace App\DTOs;

final class PriceTierDto
{
    public private(set) ?string $id;

    public private(set) ?string $planId;

    public private(set) string $metricIdentifier;

    public private(set) string $tierMode;

    public private(set) int $firstUnit;

    public private(set) ?int $lastUnit;

    public private(set) int $unitPriceCents;

    public private(set) int $flatFeeCents;

    public function __construct(
        string $metricIdentifier,
        string $tierMode = 'graduated',
        int $firstUnit = 1,
        ?int $lastUnit = null,
        int $unitPriceCents = 0,
        int $flatFeeCents = 0,
        ?string $planId = null,
        ?string $id = null,
    ) {
        $this->metricIdentifier = $metricIdentifier;
        $this->tierMode = $tierMode;
        $this->firstUnit = $firstUnit;
        $this->lastUnit = $lastUnit;
        $this->unitPriceCents = $unitPriceCents;
        $this->flatFeeCents = $flatFeeCents;
        $this->planId = $planId;
        $this->id = $id;
    }

    public bool $isUnbounded {
        get => $this->lastUnit === null;
    }

    public string $formattedUnitPrice {
        get => sprintf('$%.2f', $this->unitPriceCents / 100);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            metricIdentifier: (string) $data['metric_identifier'],
            tierMode: (string) ($data['tier_mode'] ?? 'graduated'),
            firstUnit: (int) ($data['first_unit'] ?? 1),
            lastUnit: isset($data['last_unit']) && $data['last_unit'] !== null ? (int) $data['last_unit'] : null,
            unitPriceCents: (int) ($data['unit_price_cents'] ?? 0),
            flatFeeCents: (int) ($data['flat_fee_cents'] ?? 0),
            planId: isset($data['plan_id']) ? (string) $data['plan_id'] : null,
            id: isset($data['id']) ? (string) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'plan_id' => $this->planId,
            'metric_identifier' => $this->metricIdentifier,
            'tier_mode' => $this->tierMode,
            'first_unit' => $this->firstUnit,
            'last_unit' => $this->lastUnit,
            'unit_price_cents' => $this->unitPriceCents,
            'flat_fee_cents' => $this->flatFeeCents,
        ], fn ($value) => $value !== null);
    }
}
