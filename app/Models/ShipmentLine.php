<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentLine extends Model
{
    protected $fillable = [
        'shipment_id',
        'sales_invoice_line_id',
        'item_id',
        'qty',
        'scan_code',
        'remarks',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function invoiceLine()
    {
        return $this->belongsTo(SalesInvoiceLine::class, 'sales_invoice_line_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
