<?php

declare(strict_types=1);

namespace App\Actions\Ingestion;

use App\DTOs\RecordUsageDto;
use App\DTOs\RecordUsageResultDto;
use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\UsageEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordUsageAction
{
    /**
     * Ingest a usage event idempotently.
     * Duplicate submissions return existing record without double-counting.
     */
    public function execute(RecordUsageDto $dto): RecordUsageResultDto
    {
        // 1. Initial idempotency check
        $existing = UsageEvent::query()
            ->where('customer_id', $dto->customerId)
            ->where('idempotency_key', $dto->idempotencyKey)
            ->first();

        if ($existing) {
            return new RecordUsageResultDto(
                event: $existing,
                isDuplicate: true,
            );
        }

        // 2. Resolve customer and parent merchant
        $customer = Customer::findOrFail($dto->customerId);
        $merchantId = $dto->merchantId ?? $customer->merchant_id;

        return DB::transaction(function () use ($dto, $merchantId): RecordUsageResultDto {
            // Re-verify inside transaction to handle concurrent identical requests
            $existing = UsageEvent::query()
                ->where('customer_id', $dto->customerId)
                ->where('idempotency_key', $dto->idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return new RecordUsageResultDto(
                    event: $existing,
                    isDuplicate: true,
                );
            }

            $eventId = (string) Str::uuid();

            $event = UsageEvent::create([
                'id' => $eventId,
                'merchant_id' => $merchantId,
                'customer_id' => $dto->customerId,
                'metric_identifier' => $dto->metricIdentifier,
                'quantity' => $dto->quantity,
                'idempotency_key' => $dto->idempotencyKey,
                'timestamp' => $dto->timestamp,
                'properties' => ! empty($dto->properties) ? $dto->properties : null,
            ]);

            // Atomically update daily pre-aggregated rollup
            $summary = DailyUsageSummary::firstOrCreate([
                'customer_id' => $dto->customerId,
                'metric_identifier' => $dto->metricIdentifier,
                'usage_date' => $dto->usageDate,
            ], [
                'merchant_id' => $merchantId,
                'total_quantity' => 0,
                'event_count' => 0,
                'last_aggregated_at' => now(),
            ]);

            $summary->increment('total_quantity', $dto->quantity);
            $summary->increment('event_count', 1);
            $summary->update(['last_aggregated_at' => now()]);

            return new RecordUsageResultDto(
                event: $event,
                isDuplicate: false,
            );
        });
    }
}
