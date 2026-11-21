<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = ['cash', 'bank', 'card', 'other'];

    protected $fillable = [
        'amount', 'currency', 'paid_on', 'method', 'reference', 'balance_after', 'customer_name',
        'issuer_name', 'invoice_number', 'branch', 'request_key', 'issued_by', 'prior_invoice_status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'paid_on' => 'date',
        'reversed_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by')->withTrashed();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('reversed_at');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by')->withTrashed();
    }

    public function requiresPriorStatus(): bool
    {
        // Older full receipts could either settle a pending invoice or document a previous payment.
        return $this->prior_invoice_status === null
            && (float) $this->balance_after === 0.0
            && ! static::active()->where('invoice_id', $this->invoice_id)->where('id', '<', $this->id)->exists();
    }

    public function receiptNumber(): string
    {
        return sprintf('RCP-%s-%06d', $this->created_at->year, $this->id);
    }
}
