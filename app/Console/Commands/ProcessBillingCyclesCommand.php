<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Invoicing\GenerateInvoiceAction;
use App\Actions\Invoicing\ProcessPaymentAction;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessBillingCyclesCommand extends Command
{
    protected $signature = 'billing:process-cycles';

    protected $description = 'Process recurring billing cycles, invoice generation, and payment attempts';

    public function handle(
        GenerateInvoiceAction $generateInvoice,
        ProcessPaymentAction $processPayment,
    ): int {
        $now = Carbon::now();

        $dueSubscriptions = Subscription::withoutGlobalScopes()
            ->with(['tenant', 'plan'])
            ->whereIn('status', ['active', 'past_due'])
            ->where('current_period_end', '<=', $now)
            ->get();

        $this->info(sprintf('Found %d subscriptions due for cycle renewal.', $dueSubscriptions->count()));

        $processed = 0;
        $failedPayments = 0;

        foreach ($dueSubscriptions as $subscription) {
            // Generate cycle invoice (base fee + metered overages)
            $invoice = $generateInvoice->execute($subscription, 'subscription_cycle');

            // Attempt payment
            $paymentResult = $processPayment->execute($invoice);

            if (! $paymentResult->success) {
                $failedPayments++;
            }

            // Handle period end cancellation or advance period
            if ($subscription->cancel_at_period_end) {
                $subscription->update([
                    'status' => 'canceled',
                    'ended_at' => $now,
                    'cancel_at_period_end' => false,
                ]);
            } else {
                $newStart = $subscription->current_period_end;
                $newEnd = $subscription->plan->invoice_interval === 'year'
                    ? $newStart->copy()->addYear()
                    : $newStart->copy()->addMonth();

                $subscription->update([
                    'current_period_start' => $newStart,
                    'current_period_end' => $newEnd,
                ]);
            }

            $processed++;
        }

        $this->info(sprintf('Successfully processed %d subscriptions. Failed payments: %d', $processed, $failedPayments));

        return Command::SUCCESS;
    }
}
