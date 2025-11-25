<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingPickupLine extends Model
{
    protected $fillable = [
        'sewing_pickup_id',
        'cutting_job_bundle_id',
        'finished_item_id',
        'qty_bundle',
        'qty_returned_ok',
        'qty_returned_reject',
        'status',
        'notes',
    ];

    public function pickup()
    {
        return $this->belongsTo(SewingPickup::class, 'sewing_pickup_id');
    }

    public function bundle()
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

    public function finishedItem()
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }
}
