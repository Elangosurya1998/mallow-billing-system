<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\DTOs\PaymentResultDto;
use App\Models\Tenant;

interface PaymentGatewayInterface
{
    public function charge(Tenant $tenant, int $amountCents, string $idempotencyKey): PaymentResultDto;
}
