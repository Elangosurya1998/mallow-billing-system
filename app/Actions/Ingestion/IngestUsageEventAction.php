<?php

declare(strict_types=1);

namespace App\Actions\Ingestion;

use App\DTOs\UsageEventDto;
use App\Models\UsageEvent;
use App\Models\UsageSummary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IngestUsageEventAction
{
    /**
     * Ingest a single usage event idempotently and update daily pre-aggregations.
     */
    public function execute(UsageEventDto $dto): UsageEvent
    {
        $cacheKey = "idemp:{$dto->tenantId}:{$dto->idempotencyKey}";

        // Fast lock / idempotency check via cache
        $lockAcquired = Cache::add($cacheKey, true, now()->addDay());

        if (! $lockAcquired) {
            // Check if record already exists in database
            $existing = UsageEvent::withoutGlobalScopes()
                ->where('tenant_id', $dto->tenantId)
                ->where('idempotency_key', $dto->idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($dto): UsageEvent {
            $eventId = $dto->id ?? (string) Str::uuid();

            $event = UsageEvent::withoutGlobalScopes()->create([
                'id' => $eventId,
                'tenant_id' => $dto->tenantId,
                'metric_identifier' => $dto->metricIdentifier,
                'quantity' => $dto->quantity,
                'idempotency_key' => $dto->idempotencyKey,
                'timestamp' => $dto->timestamp,
                'properties' => $dto->properties,
            ]);

            // Atomically update pre-aggregated daily summary
            $summary = UsageSummary::withoutGlobalScopes()->firstOrCreate([
                'tenant_id' => $dto->tenantId,
                'metric_identifier' => $dto->metricIdentifier,
                'usage_date' => $dto->usageDate,
            ], [
                'total_quantity' => 0,
                'event_count' => 0,
                'last_aggregated_at' => now(),
            ]);

            $summary->increment('total_quantity', $dto->quantity);
            $summary->increment('event_count', 1);
            $summary->update(['last_aggregated_at' => now()]);

            return $event;
        });
    }
}
