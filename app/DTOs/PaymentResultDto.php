<?php

declare(strict_types=1);

namespace App\DTOs;

final class PaymentResultDto
{
    public private(set) bool $success;

    public private(set) string $transactionId;

    public private(set) int $amountCents;

    public private(set) ?string $failureReason;

    public function __construct(
        bool $success,
        string $transactionId,
        int $amountCents,
        ?string $failureReason = null,
    ) {
        $this->success = $success;
        $this->transactionId = $transactionId;
        $this->amountCents = $amountCents;
        $this->failureReason = $failureReason;
    }

    public string $formattedAmount {
        get => sprintf('$%.2f', $this->amountCents / 100);
    }

    public static function successful(string $transactionId, int $amountCents): self
    {
        return new self(
            success: true,
            transactionId: $transactionId,
            amountCents: $amountCents,
        );
    }

    public static function failed(string $transactionId, int $amountCents, string $reason): self
    {
        return new self(
            success: false,
            transactionId: $transactionId,
            amountCents: $amountCents,
            failureReason: $reason,
        );
    }
}
