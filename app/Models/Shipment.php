<?php

namespace App\Models;

use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'store_id',
        'sales_invoice_id', // ← tambahkan ini
        'date',
        'status',
        'notes',
        'total_qty',
        'created_by',
        'submitted_at',
        'submitted_by',
        'posted_at',
        'posted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function lines()
    {
        return $this->hasMany(ShipmentLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public static function generateCode(): string
    {
        $today = now()->format('Ymd');

        $last = static::whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($last && preg_match('/SHP-' . $today . '-(\d+)/', $last->code, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return 'SHP-' . $today . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
