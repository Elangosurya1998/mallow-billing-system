<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPeriod extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'subscription_id',
        'plan_id',
        'period_start',
        'period_end',
        'status',
        'subtotal_cents',
        'total_cents',
        'prorated_base_price_cents',
        'prorated_allowance_units',
        'overage_rate_cents',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'subtotal_cents' => 'integer',
            'total_cents' => 'integer',
            'prorated_base_price_cents' => 'integer',
            'prorated_allowance_units' => 'integer',
            'overage_rate_cents' => 'integer',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
