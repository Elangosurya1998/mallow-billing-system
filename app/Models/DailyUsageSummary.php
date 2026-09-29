<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyUsageSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'metric_identifier',
        'usage_date',
        'total_quantity',
        'event_count',
        'last_aggregated_at',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date:Y-m-d',
            'total_quantity' => 'integer',
            'event_count' => 'integer',
            'last_aggregated_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
