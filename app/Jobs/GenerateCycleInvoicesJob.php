<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DailyUsageSummary;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SubscriptionPeriod;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateCycleInvoicesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ?string $asOfDate = null,
    ) {}

    public function handle(): void
    {
        $asOf = $this->asOfDate ? Carbon::parse($this->asOfDate) : Carbon::now();

        // Process active periods due at cycle end in chunks of 1,000
        SubscriptionPeriod::query()
            ->with(['subscription.customer', 'subscription.merchant', 'subscription.plan', 'plan'])
            ->where('status', 'active')
            ->where('period_end', '<=', $asOf)
            ->chunkById(1000, function (Collection $periods) use ($asOf): void {
                /** @var SubscriptionPeriod $period */
                foreach ($periods as $period) {
                    $this->processPeriodInvoice($period, $asOf);
                }
            });
    }

    private function processPeriodInvoice(SubscriptionPeriod $period, CarbonInterface $asOf): void
    {
        DB::transaction(function () use ($period, $asOf): void {
            $subscription = $period->subscription;
            $customer = $subscription->customer;
            $merchant = $subscription->merchant;
            $activePlan = $period->plan ?? $subscription->plan;

            // Retrieve all unbilled segments for this cycle (including any closed switch segments)
            $segments = $subscription->periods()
                ->with('plan')
                ->where(function ($q) use ($period) {
                    $q->where('id', $period->id)
                        ->orWhere(function ($q2) use ($period) {
                            $q2->where('status', 'closed')
                                ->whereDoesntHave('invoices')
                                ->where('period_end', '<=', $period->period_end);
                        });
                })
                ->orderBy('period_start')
                ->get();

            $lineItemsData = [];

            foreach ($segments as $segment) {
                $segPlan = $segment->plan ?? $activePlan;

                // Base fee for segment
                $baseFeeCents = $segment->prorated_base_price_cents > 0
                    ? $segment->prorated_base_price_cents
                    : ($segPlan->base_price_cents * $subscription->quantity);

                $allowance = $segment->prorated_allowance_units > 0
                    ? $segment->prorated_allowance_units
                    : ($segPlan->included_units * $subscription->quantity);

                $overageRateCents = $segment->overage_rate_cents > 0
                    ? $segment->overage_rate_cents
                    : (int) $segPlan->overage_unit_price_cents;

                // Query pre-aggregated daily summaries strictly avoiding raw event scans
                $totalUsage = (int) DailyUsageSummary::query()
                    ->where('customer_id', $customer->id)
                    ->whereBetween('usage_date', [
                        $segment->period_start->toDateString(),
                        $segment->period_end->toDateString(),
                    ])
                    ->sum('total_quantity');

                $overageUnits = max(0, $totalUsage - $allowance);
                $overageCents = $overageUnits * $overageRateCents;

                // Base Fee Line Item
                if ($baseFeeCents > 0) {
                    $lineItemsData[] = [
                        'description' => sprintf(
                            '%s Base Fee (%s to %s)',
                            $segPlan->name,
                            $segment->period_start->format('M d'),
                            $segment->period_end->format('M d')
                        ),
                        'metric_identifier' => null,
                        'quantity' => $subscription->quantity,
                        'unit_price_cents' => $baseFeeCents,
                        'subtotal_cents' => $baseFeeCents,
                        'is_proration' => $segment->prorated_base_price_cents > 0,
                        'metadata' => [
                            'segment_id' => $segment->id,
                            'start' => $segment->period_start->toIso8601String(),
                            'end' => $segment->period_end->toIso8601String(),
                        ],
                    ];
                }

                // Overage Line Item
                if ($overageUnits > 0 && $overageCents > 0) {
                    $lineItemsData[] = [
                        'description' => sprintf(
                            'Usage overage (%s units exceeding %s allowance)',
                            number_format($overageUnits),
                            number_format($allowance)
                        ),
                        'metric_identifier' => 'usage_overage',
                        'quantity' => $overageUnits,
                        'unit_price_cents' => $overageRateCents,
                        'subtotal_cents' => $overageCents,
                        'is_proration' => false,
                        'metadata' => [
                            'segment_id' => $segment->id,
                            'total_usage' => $totalUsage,
                            'allowance' => $allowance,
                        ],
                    ];
                }
            }

            $grossSubtotal = array_sum(array_column($lineItemsData, 'subtotal_cents'));

            // Offset against customer credit balance if available
            $creditOffset = 0;
            if ($customer->credit_balance_cents > 0 && $grossSubtotal > 0) {
                $creditOffset = min($customer->credit_balance_cents, $grossSubtotal);
                $customer->decrement('credit_balance_cents', $creditOffset);

                $lineItemsData[] = [
                    'description' => 'Credit balance applied',
                    'metric_identifier' => null,
                    'quantity' => 1,
                    'unit_price_cents' => -$creditOffset,
                    'subtotal_cents' => -$creditOffset,
                    'is_proration' => true,
                    'metadata' => ['type' => 'credit_deduction'],
                ];
            }

            $netTotal = max(0, $grossSubtotal - $creditOffset);
            $invoiceNumber = sprintf('INV-%s-%s', date('Y'), strtoupper(Str::random(8)));

            $invoice = Invoice::create([
                'merchant_id' => $merchant->id,
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'subscription_period_id' => $period->id,
                'invoice_number' => $invoiceNumber,
                'status' => 'open',
                'subtotal_cents' => $grossSubtotal,
                'tax_cents' => 0,
                'total_cents' => $netTotal,
                'amount_paid_cents' => 0,
                'amount_remaining_cents' => $netTotal,
                'due_date' => Carbon::now()->addDays(7),
                'billing_reason' => 'subscription_cycle',
            ]);

            foreach ($lineItemsData as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'metric_identifier' => $item['metric_identifier'],
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $item['unit_price_cents'],
                    'subtotal_cents' => $item['subtotal_cents'],
                    'is_proration' => $item['is_proration'],
                    'metadata' => $item['metadata'],
                ]);
            }

            // Mark segments as billed
            foreach ($segments as $segment) {
                $segment->update(['status' => 'billed']);
            }

            // Advance subscription to next cycle or cancel
            if ($subscription->cancel_at_period_end) {
                $subscription->update([
                    'status' => 'canceled',
                    'ended_at' => $asOf,
                    'cancel_at_period_end' => false,
                ]);
            } else {
                $nextStart = $period->period_end;
                $nextEnd = $activePlan->invoice_interval === 'year'
                    ? $nextStart->copy()->addYear()
                    : $nextStart->copy()->addMonth();

                SubscriptionPeriod::create([
                    'subscription_id' => $subscription->id,
                    'plan_id' => $activePlan->id,
                    'period_start' => $nextStart,
                    'period_end' => $nextEnd,
                    'status' => 'active',
                    'prorated_base_price_cents' => $activePlan->base_price_cents * $subscription->quantity,
                    'prorated_allowance_units' => $activePlan->included_units * $subscription->quantity,
                    'overage_rate_cents' => $activePlan->overage_unit_price_cents,
                    'subtotal_cents' => $activePlan->base_price_cents * $subscription->quantity,
                    'total_cents' => $activePlan->base_price_cents * $subscription->quantity,
                ]);
            }
        });
    }
}
