<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'status', 'customer_name', 'customer_email', 'customer_phone', 'customer_address',
        'invoice_date', 'due_date', 'currency', 'tax_rate', 'discount_amount', 'notes', 'terms', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'tax_rate' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItem::class)->orderBy('sort_order');
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->lineItems->sum(fn ($li) => $li->quantity * $li->unit_price);
    }

    public function getTaxAmountAttribute(): float
    {
        return round($this->subtotal * ($this->tax_rate / 100), 2);
    }

    public function getTotalAttribute(): float
    {
        return round($this->subtotal + $this->tax_amount - (float) $this->discount_amount, 2);
    }
}
