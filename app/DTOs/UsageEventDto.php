<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class UsageEventDto
{
    public private(set) ?string $id;

    public private(set) string $tenantId;

    public private(set) string $metricIdentifier;

    public private(set) int $quantity;

    public private(set) string $idempotencyKey;

    public private(set) string $timestamp;

    public private(set) array $properties;

    public function __construct(
        string $tenantId,
        string $metricIdentifier,
        int $quantity,
        string $idempotencyKey,
        string|CarbonInterface $timestamp,
        array $properties = [],
        ?string $id = null,
    ) {
        $this->tenantId = $tenantId;
        $this->metricIdentifier = $metricIdentifier;
        $this->quantity = $quantity;
        $this->idempotencyKey = $idempotencyKey;
        $this->timestamp = $timestamp instanceof CarbonInterface
            ? $timestamp->toIso8601String()
            : Carbon::parse($timestamp)->toIso8601String();
        $this->properties = $properties;
        $this->id = $id;
    }

    public string $usageDate {
        get => Carbon::parse($this->timestamp)->format('Y-m-d');
    }

    public static function fromArray(array $data, ?string $tenantId = null): self
    {
        return new self(
            tenantId: (string) ($tenantId ?? $data['tenant_id']),
            metricIdentifier: (string) $data['metric_identifier'],
            quantity: (int) ($data['quantity'] ?? 1),
            idempotencyKey: (string) $data['idempotency_key'],
            timestamp: (string) ($data['timestamp'] ?? now()->toIso8601String()),
            properties: (array) ($data['properties'] ?? []),
            id: isset($data['id']) ? (string) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'metric_identifier' => $this->metricIdentifier,
            'quantity' => $this->quantity,
            'idempotency_key' => $this->idempotencyKey,
            'timestamp' => $this->timestamp,
            'properties' => $this->properties,
        ], fn ($val) => $val !== null);
    }
}
