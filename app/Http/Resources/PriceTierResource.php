<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PriceTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PriceTier
 */
class PriceTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'metric_identifier' => $this->metric_identifier,
            'tier_mode' => $this->tier_mode,
            'first_unit' => $this->first_unit,
            'last_unit' => $this->last_unit,
            'unit_price_cents' => $this->unit_price_cents,
            'flat_fee_cents' => $this->flat_fee_cents,
            'is_unbounded' => $this->isUnbounded(),
        ];
    }
}
