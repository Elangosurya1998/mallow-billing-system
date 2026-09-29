<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Subscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'plan_id',
        'status',
        'quantity',
        'trial_ends_at',
        'canceled_at',
        'ended_at',
        'cancel_at_period_end',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'trial_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'ended_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(SubscriptionPeriod::class)->orderBy('period_start', 'desc');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function currentPeriod(): ?SubscriptionPeriod
    {
        return $this->periods()
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first() ?? $this->periods()->first();
    }

    public function getCurrentPeriodStartAttribute(): ?CarbonInterface
    {
        return $this->currentPeriod()?->period_start ?? $this->created_at ?? Carbon::now()->startOfMonth();
    }

    public function getCurrentPeriodEndAttribute(): ?CarbonInterface
    {
        return $this->currentPeriod()?->period_end ?? ($this->created_at ? Carbon::parse($this->created_at)->addMonth() : Carbon::now()->endOfMonth());
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ['active', 'trialing']);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true);
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }
}
