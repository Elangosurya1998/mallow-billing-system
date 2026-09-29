<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class RecordUsageDto
{
    public private(set) string $customerId;

    public private(set) string $metricIdentifier;

    public private(set) int $quantity;

    public private(set) string $idempotencyKey;

    public private(set) string $timestamp;

    public private(set) array $properties;

    public private(set) ?string $merchantId;

    public function __construct(
        string $customerId,
        string $metricIdentifier,
        int $quantity,
        string $idempotencyKey,
        string|CarbonInterface|null $timestamp = null,
        array $properties = [],
        ?string $merchantId = null,
    ) {
        $this->customerId = $customerId;
        $this->metricIdentifier = $metricIdentifier;
        $this->quantity = $quantity;
        $this->idempotencyKey = $idempotencyKey;
        $this->timestamp = $timestamp instanceof CarbonInterface
            ? $timestamp->toIso8601String()
            : ($timestamp ? Carbon::parse($timestamp)->toIso8601String() : Carbon::now()->toIso8601String());
        $this->properties = $properties;
        $this->merchantId = $merchantId;
    }

    public string $usageDate {
        get => Carbon::parse($this->timestamp)->format('Y-m-d');
    }

    public static function fromArray(array $data, ?string $merchantId = null): self
    {
        return new self(
            customerId: (string) $data['customer_id'],
            metricIdentifier: (string) $data['metric_identifier'],
            quantity: (int) $data['quantity'],
            idempotencyKey: (string) $data['idempotency_key'],
            timestamp: isset($data['timestamp']) ? (string) $data['timestamp'] : null,
            properties: (array) ($data['properties'] ?? []),
            merchantId: $merchantId ?? ($data['merchant_id'] ?? null),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'merchant_id' => $this->merchantId,
            'customer_id' => $this->customerId,
            'metric_identifier' => $this->metricIdentifier,
            'quantity' => $this->quantity,
            'idempotency_key' => $this->idempotencyKey,
            'timestamp' => $this->timestamp,
            'properties' => $this->properties,
        ], fn ($val) => $val !== null);
    }
}
