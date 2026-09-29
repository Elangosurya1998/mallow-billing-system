<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'currency',
        'status',
        'timezone',
    ];

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function usageEvents(): HasMany
    {
        return $this->hasMany(UsageEvent::class);
    }

    public function dailyUsageSummaries(): HasMany
    {
        return $this->hasMany(DailyUsageSummary::class);
    }
}
