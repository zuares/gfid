<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'province',
        'postal_code',
        'notes',
        'created_by',
        'updated_by',
    ];

    // Relasi-relasi penting (bisa diaktifkan kalau modelnya sudah ada)
    public function marketplaceOrders()
    {
        return $this->hasMany(MarketplaceOrder::class);
    }

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
