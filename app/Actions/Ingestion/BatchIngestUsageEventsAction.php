<?php

declare(strict_types=1);

namespace App\Actions\Ingestion;

use App\DTOs\UsageBatchDto;
use App\Models\UsageEvent;
use App\Models\UsageSummary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BatchIngestUsageEventsAction
{
    /**
     * Ingest a batch of usage events with grouped pre-aggregation updates.
     *
     * @return array{ingested: int, duplicates: int, total_quantity: int}
     */
    public function execute(UsageBatchDto $batchDto): array
    {
        if (empty($batchDto->events)) {
            return ['ingested' => 0, 'duplicates' => 0, 'total_quantity' => 0];
        }

        $tenantId = $batchDto->tenantId;
        $uniqueEvents = [];
        $duplicatesCount = 0;
        $seenKeys = [];

        // In-memory deduplication of the batch
        foreach ($batchDto->events as $event) {
            if (isset($seenKeys[$event->idempotencyKey])) {
                $duplicatesCount++;

                continue;
            }

            $cacheKey = "idemp:{$tenantId}:{$event->idempotencyKey}";
            if (! Cache::add($cacheKey, true, now()->addDay())) {
                // Check if already in DB
                $exists = UsageEvent::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('idempotency_key', $event->idempotencyKey)
                    ->exists();

                if ($exists) {
                    $duplicatesCount++;

                    continue;
                }
            }

            $seenKeys[$event->idempotencyKey] = true;
            $uniqueEvents[] = $event;
        }

        if (empty($uniqueEvents)) {
            return [
                'ingested' => 0,
                'duplicates' => $duplicatesCount,
                'total_quantity' => 0,
            ];
        }

        return DB::transaction(function () use ($tenantId, $uniqueEvents, $duplicatesCount): array {
            $insertRows = [];
            $aggregates = []; // key: metric:date => ['quantity' => sum, 'count' => count, 'metric' => ..., 'date' => ...]
            $now = now()->toDateTimeString();

            foreach ($uniqueEvents as $event) {
                $eventId = $event->id ?? (string) Str::uuid();
                $usageDate = $event->usageDate;
                $metric = $event->metricIdentifier;

                $insertRows[] = [
                    'id' => $eventId,
                    'tenant_id' => $tenantId,
                    'metric_identifier' => $metric,
                    'quantity' => $event->quantity,
                    'idempotency_key' => $event->idempotencyKey,
                    'timestamp' => $event->timestamp,
                    'properties' => ! empty($event->properties) ? json_encode($event->properties) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $aggKey = "{$metric}:{$usageDate}";
                if (! isset($aggregates[$aggKey])) {
                    $aggregates[$aggKey] = [
                        'metric' => $metric,
                        'date' => $usageDate,
                        'quantity' => 0,
                        'count' => 0,
                    ];
                }
                $aggregates[$aggKey]['quantity'] += $event->quantity;
                $aggregates[$aggKey]['count'] += 1;
            }

            // Bulk insert raw events in chunks of 500
            foreach (array_chunk($insertRows, 500) as $chunk) {
                UsageEvent::withoutGlobalScopes()->insert($chunk);
            }

            // Update daily summaries with pre-computed group totals
            $totalQuantityIngested = 0;
            foreach ($aggregates as $agg) {
                $summary = UsageSummary::withoutGlobalScopes()->firstOrCreate([
                    'tenant_id' => $tenantId,
                    'metric_identifier' => $agg['metric'],
                    'usage_date' => $agg['date'],
                ], [
                    'total_quantity' => 0,
                    'event_count' => 0,
                    'last_aggregated_at' => now(),
                ]);

                $summary->increment('total_quantity', $agg['quantity']);
                $summary->increment('event_count', $agg['count']);
                $summary->update(['last_aggregated_at' => now()]);

                $totalQuantityIngested += $agg['quantity'];
            }

            return [
                'ingested' => count($uniqueEvents),
                'duplicates' => $duplicatesCount,
                'total_quantity' => $totalQuantityIngested,
            ];
        });
    }
}
