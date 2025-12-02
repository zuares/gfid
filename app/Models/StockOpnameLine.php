<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameLine extends Model
{
    protected $fillable = [
        'stock_opname_id',
        'item_id',
        'system_qty',
        'physical_qty',
        'difference_qty',
        'is_counted',
        'notes',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'physical_qty' => 'decimal:3',
        'difference_qty' => 'decimal:3',
        'is_counted' => 'boolean',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getDifferenceAttribute(): float
    {
        if (!is_null($this->difference_qty)) {
            return (float) $this->difference_qty;
        }

        return (float) ($this->physical_qty ?? 0) - (float) ($this->system_qty ?? 0);
    }

}
