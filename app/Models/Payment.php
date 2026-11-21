<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = ['cash', 'bank', 'card', 'other'];

    protected $fillable = [
        'amount', 'currency', 'paid_on', 'method', 'reference', 'balance_after', 'customer_name',
        'issuer_name', 'invoice_number', 'branch', 'request_key', 'issued_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'paid_on' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by')->withTrashed();
    }

    public function receiptNumber(): string
    {
        return sprintf('RCP-%s-%06d', $this->created_at->year, $this->id);
    }
}
