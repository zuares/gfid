<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'date',
        'supplier_id',
        'subtotal',
        'discount',
        'tax_percent',
        'tax_amount',
        'shipping_cost',
        'grand_total',
        'status',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /* ==========================
     *  RELATIONSHIPS
     * ==========================
     */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function receives()
    {
        return $this->hasMany(PurchaseReceive::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* ==========================
     *  HELPER / SCOPE
     * ==========================
     */

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }
}
