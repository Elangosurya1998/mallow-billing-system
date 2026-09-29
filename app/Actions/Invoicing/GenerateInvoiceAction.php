<?php

declare(strict_types=1);

namespace App\Actions\Invoicing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use App\Services\Billing\CalculateMeteredUsageService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateInvoiceAction
{
    public function __construct(
        private readonly CalculateMeteredUsageService $meteredUsageService,
    ) {}

    public function execute(
        Subscription $subscription,
        string $billingReason = 'subscription_cycle',
        bool $includeBaseFee = true,
    ): Invoice {
        return DB::transaction(function () use ($subscription, $billingReason, $includeBaseFee): Invoice {
            $tenant = $subscription->tenant;
            $plan = $subscription->plan;
            $itemsData = [];

            // 1. Base Subscription Recurring Fee
            if ($includeBaseFee && $plan->base_price_cents > 0) {
                $baseSubtotal = $plan->base_price_cents * $subscription->quantity;
                $itemsData[] = [
                    'description' => sprintf('%s (%d seat%s)', $plan->name, $subscription->quantity, $subscription->quantity > 1 ? 's' : ''),
                    'metric_identifier' => null,
                    'quantity' => $subscription->quantity,
                    'unit_price_cents' => $plan->base_price_cents,
                    'subtotal_cents' => $baseSubtotal,
                    'is_proration' => false,
                    'metadata' => [
                        'type' => 'base_fee',
                        'period_start' => $subscription->current_period_start->toIso8601String(),
                        'period_end' => $subscription->current_period_end->toIso8601String(),
                    ],
                ];
            }

            // 2. Metered Usage Overages
            $meteredItems = $this->meteredUsageService->calculate(
                tenant: $tenant,
                plan: $plan,
                periodStart: $subscription->current_period_start,
                periodEnd: $subscription->current_period_end,
            );

            foreach ($meteredItems as $metered) {
                $itemsData[] = [
                    'description' => $metered['description'],
                    'metric_identifier' => $metered['metric_identifier'],
                    'quantity' => $metered['quantity'],
                    'unit_price_cents' => $metered['unit_price_cents'],
                    'subtotal_cents' => $metered['subtotal_cents'],
                    'is_proration' => false,
                    'metadata' => $metered['metadata'],
                ];
            }

            $grossSubtotal = array_sum(array_column($itemsData, 'subtotal_cents'));

            // 3. Apply Credit Balance Offset if tenant has accrued proration credits
            $creditDeducted = 0;
            if ($tenant->credit_balance_cents > 0 && $grossSubtotal > 0) {
                $creditDeducted = min($tenant->credit_balance_cents, $grossSubtotal);
                $tenant->decrement('credit_balance_cents', $creditDeducted);

                $itemsData[] = [
                    'description' => 'Credit balance applied',
                    'metric_identifier' => null,
                    'quantity' => 1,
                    'unit_price_cents' => -$creditDeducted,
                    'subtotal_cents' => -$creditDeducted,
                    'is_proration' => true,
                    'metadata' => ['type' => 'credit_deduction'],
                ];
            }

            $netTotal = max(0, $grossSubtotal - $creditDeducted);

            // Generate unique invoice number: INV-YEAR-RANDOMHEX
            $invoiceNumber = sprintf('INV-%s-%s', date('Y'), strtoupper(Str::random(8)));

            $invoice = Invoice::withoutGlobalScopes()->create([
                'merchant_id' => $tenant->id,
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'status' => 'open',
                'subtotal_cents' => $grossSubtotal,
                'tax_cents' => 0,
                'total_cents' => $netTotal,
                'amount_paid_cents' => 0,
                'amount_remaining_cents' => $netTotal,
                'due_date' => Carbon::now()->addDays(7),
                'billing_reason' => $billingReason,
            ]);

            foreach ($itemsData as $item) {
                InvoiceItem::withoutGlobalScopes()->create([
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

            return $invoice->load('items');
        });
    }
}
