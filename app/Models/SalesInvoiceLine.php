<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceLine extends Model
{
    protected $table = 'sales_invoice_lines';

    protected $fillable = [
        'sales_invoice_id',
        'item_id',
        'qty',
        'unit_price',
        'line_discount',
        'line_total',
        'hpp_unit_snapshot',
        'margin_unit',
        'margin_total',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
