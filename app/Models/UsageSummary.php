<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageSummary extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'metric_identifier',
        'usage_date',
        'total_quantity',
        'event_count',
        'last_aggregated_at',
    ];

    protected function casts(): array
    {
        return [
            'total_quantity' => 'integer',
            'event_count' => 'integer',
            'usage_date' => 'date:Y-m-d',
            'last_aggregated_at' => 'datetime',
        ];
    }
}
