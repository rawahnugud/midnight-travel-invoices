<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const BRANCHES = ['SD', 'AE', 'GB'];

    protected $fillable = [
        'branch', 'invoice_number', 'status', 'customer_name', 'customer_email', 'customer_phone', 'customer_address',
        'invoice_date', 'due_date', 'currency', 'tax_rate', 'discount_amount', 'notes', 'terms', 'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'tax_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function issuerName(?BusinessSetting $business): string
    {
        return $this->branch ? __('messages.branch_'.$this->branch) : ($business?->company_name ?? __('messages.app_name'));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function outstandingAmount(): float
    {
        if ($this->status === 'paid') {
            return 0.0;
        }

        $latestPayment = $this->relationLoaded('payments')
            ? $this->payments->sortByDesc('id')->first()
            : $this->payments()->latest('id')->first();

        return max(0.0, (float) ($latestPayment?->balance_after ?? $this->total));
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
