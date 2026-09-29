<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'name' => $this->name,
            'email' => $this->email,
            'currency' => $this->currency,
            'credit_balance_cents' => $this->credit_balance_cents,
            'credit_balance_formatted' => sprintf('%s %.2f', $this->currency, $this->credit_balance_cents / 100),
            'external_reference' => $this->external_reference,
            'timezone' => $this->timezone,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
