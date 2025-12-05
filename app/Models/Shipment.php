<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'code',
        'date',
        'sales_invoice_id',
        'customer_id',
        'warehouse_id',
        'status',
        'shipping_method',
        'tracking_no',
        'shipping_address',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines()
    {
        return $this->hasMany(ShipmentLine::class);
    }
}
