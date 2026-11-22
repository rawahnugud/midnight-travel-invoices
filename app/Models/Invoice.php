<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    public const BRANCHES = ['SD', 'AE', 'GB'];

    public const DEFAULT_STATUS = 'pending';

    protected $attributes = ['status' => self::DEFAULT_STATUS];

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
        if (in_array($this->branch, ['AE', 'GB'], true) && $business) {
            return $business->invoiceDetails($this->branch)->company_name;
        }
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

    public function scopeListed(Builder $query): Builder
    {
        return $query->whereNotExists(fn ($removed) => $removed->selectRaw('1')
            ->from('invoice_removals')->whereColumn('invoice_removals.invoice_id', 'invoices.id'));
    }

    public function lockForPaymentUpdate(): self
    {
        // Acquire a writer lock on SQLite and a row lock on MySQL before reading the balance.
        DB::table('invoices')->where('id', $this->id)->update(['id' => DB::raw('id')]);
        return self::findOrFail($this->id);
    }

    public function latestActivePayment(): ?Payment
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->whereNull('reversed_at')->sortByDesc('id')->first();
        }

        return $this->payments()->active()->latest('id')->first();
    }

    public function outstandingAmount(): float
    {
        if ($this->status === 'paid') {
            return 0.0;
        }

        return max(0.0, (float) ($this->latestActivePayment()?->balance_after ?? $this->total));
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
