<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineItem extends Model
{
    protected $fillable = ['invoice_id', 'item_name', 'description', 'quantity', 'unit_price', 'sort_order'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getLineTotalAttribute(): float
    {
        return (float) ($this->quantity * $this->unit_price);
    }
}
