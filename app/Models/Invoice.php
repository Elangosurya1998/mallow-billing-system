<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'subscription_id',
        'subscription_period_id',
        'invoice_number',
        'status',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'amount_paid_cents',
        'amount_remaining_cents',
        'due_date',
        'paid_at',
        'billing_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'amount_paid_cents' => 'integer',
            'amount_remaining_cents' => 'integer',
            'due_date' => 'datetime',
            'paid_at' => 'datetime',
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPeriod::class, 'subscription_period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
