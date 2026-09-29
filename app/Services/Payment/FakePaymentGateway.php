<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\DTOs\PaymentResultDto;
use App\Models\Tenant;
use Illuminate\Support\Str;

class FakePaymentGateway implements PaymentGatewayInterface
{
    private bool $shouldFail = false;

    private ?string $failureReason = null;

    public function setFailure(bool $shouldFail, ?string $reason = 'Card declined.'): void
    {
        $this->shouldFail = $shouldFail;
        $this->failureReason = $reason;
    }

    public function charge(Tenant $tenant, int $amountCents, string $idempotencyKey): PaymentResultDto
    {
        $transactionId = 'txn_fake_'.Str::random(16);

        if ($this->shouldFail) {
            return PaymentResultDto::failed(
                transactionId: $transactionId,
                amountCents: $amountCents,
                reason: $this->failureReason ?? 'Insufficient funds.'
            );
        }

        return PaymentResultDto::successful(
            transactionId: $transactionId,
            amountCents: $amountCents
        );
    }
}
