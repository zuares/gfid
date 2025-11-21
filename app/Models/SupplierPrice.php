<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'item_id',
        'last_price',
    ];

    protected $casts = [
        'last_price' => 'decimal:2',
    ];

    /* ==========================
     *  RELATIONSHIPS
     * ==========================
     */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);

    }

}
