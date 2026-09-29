<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DailyUsageSummary;
use App\Models\UsageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AggregateDailyUsageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ?string $date = null,
        public readonly ?string $customerId = null,
    ) {}

    public function handle(): void
    {
        $query = UsageEvent::query();

        if ($this->date) {
            $start = Carbon::parse($this->date)->startOfDay();
            $end = Carbon::parse($this->date)->endOfDay();
            $query->whereBetween('timestamp', [$start, $end]);
        }

        if ($this->customerId) {
            $query->where('customer_id', $this->customerId);
        }

        // Process raw events in chunks of 5,000
        $query->chunkById(5000, function (Collection $chunk): void {
            $groupedAggregates = [];

            /** @var UsageEvent $event */
            foreach ($chunk as $event) {
                $usageDate = $event->timestamp->toDateString();
                $key = "{$event->customer_id}:{$event->metric_identifier}:{$usageDate}";

                if (! isset($groupedAggregates[$key])) {
                    $groupedAggregates[$key] = [
                        'customer_id' => $event->customer_id,
                        'merchant_id' => $event->merchant_id,
                        'metric_identifier' => $event->metric_identifier,
                        'usage_date' => $usageDate,
                        'total_quantity' => 0,
                        'event_count' => 0,
                    ];
                }

                $groupedAggregates[$key]['total_quantity'] += $event->quantity;
                $groupedAggregates[$key]['event_count'] += 1;
            }

            foreach ($groupedAggregates as $agg) {
                $summary = DailyUsageSummary::firstOrCreate([
                    'customer_id' => $agg['customer_id'],
                    'metric_identifier' => $agg['metric_identifier'],
                    'usage_date' => $agg['usage_date'],
                ], [
                    'merchant_id' => $agg['merchant_id'],
                    'total_quantity' => 0,
                    'event_count' => 0,
                    'last_aggregated_at' => now(),
                ]);

                $summary->increment('total_quantity', $agg['total_quantity']);
                $summary->increment('event_count', $agg['event_count']);
                $summary->update(['last_aggregated_at' => now()]);
            }
        });
    }
}
