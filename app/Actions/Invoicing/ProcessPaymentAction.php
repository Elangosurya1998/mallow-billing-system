<?php

declare(strict_types=1);

namespace App\Actions\Invoicing;

use App\DTOs\PaymentResultDto;
use App\Models\Invoice;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessPaymentAction
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    public function execute(Invoice $invoice): PaymentResultDto
    {
        if ($invoice->isPaid()) {
            return PaymentResultDto::successful('already_paid', $invoice->total_cents);
        }

        // If invoice is $0.00 (e.g., covered completely by credits or trial)
        if ($invoice->total_cents === 0) {
            $invoice->update([
                'status' => 'paid',
                'amount_paid_cents' => 0,
                'amount_remaining_cents' => 0,
                'paid_at' => Carbon::now(),
            ]);

            return PaymentResultDto::successful('zero_dollar_invoice', 0);
        }

        $idempotencyKey = "inv_pay_{$invoice->id}_{$invoice->amount_remaining_cents}";
        $result = $this->gateway->charge($invoice->tenant, $invoice->amount_remaining_cents, $idempotencyKey);

        DB::transaction(function () use ($invoice, $result): void {
            if ($result->success) {
                $invoice->update([
                    'status' => 'paid',
                    'amount_paid_cents' => $invoice->total_cents,
                    'amount_remaining_cents' => 0,
                    'paid_at' => Carbon::now(),
                ]);

                // Restore subscription to active if it was past due
                if ($invoice->subscription && $invoice->subscription->status === 'past_due') {
                    $invoice->subscription->update(['status' => 'active']);
                }
            } else {
                $invoice->update([
                    'status' => 'payment_failed',
                ]);

                // Transition subscription to past_due on failure
                if ($invoice->subscription && $invoice->subscription->status === 'active') {
                    $invoice->subscription->update(['status' => 'past_due']);
                }
            }
        });

        return $result;
    }
}
