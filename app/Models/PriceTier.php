<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceTier extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'plan_id',
        'metric_identifier',
        'tier_mode',
        'first_unit',
        'last_unit',
        'unit_price_cents',
        'flat_fee_cents',
    ];

    protected function casts(): array
    {
        return [
            'first_unit' => 'integer',
            'last_unit' => 'integer',
            'unit_price_cents' => 'integer',
            'flat_fee_cents' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isUnbounded(): bool
    {
        return $this->last_unit === null;
    }
}
