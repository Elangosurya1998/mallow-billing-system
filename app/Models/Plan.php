<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'merchant_id',
        'name',
        'slug',
        'description',
        'invoice_interval',
        'base_price_cents',
        'included_units',
        'overage_unit_price_cents',
        'trial_period_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price_cents' => 'integer',
            'included_units' => 'integer',
            'overage_unit_price_cents' => 'integer',
            'trial_period_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(SubscriptionPeriod::class);
    }
}
