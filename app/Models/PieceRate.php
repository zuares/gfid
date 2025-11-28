<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PieceRate extends Model
{
    protected $fillable = [
        'module',
        'employee_id',
        'item_category_id',
        'item_id',
        'rate_per_pcs',
        'effective_from',
        'effective_to',
        'notes',
    ];

    protected $casts = [
        'rate_per_pcs' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // =====================
    // SCOPES / HELPERS
    // =====================

    public function scopeCutting($query)
    {
        return $query->where('module', 'cutting');
    }

    public function scopeActiveAt($query, $date)
    {
        return $query
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }
}
