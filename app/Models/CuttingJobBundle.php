<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuttingJobBundle extends Model
{
    protected $fillable = [
        'cutting_job_id',
        'bundle_code',
        'bundle_no',
        'lot_id',
        'finished_item_id',
        'qty_pcs',
        'qty_used_fabric',
        'operator_id',
        'status',
        'notes',
        'qty_qc_ok',
        'qty_qc_reject',
        'wip_warehouse_id',
        'wip_qty',
    ];

    public function cuttingJob()
    {
        return $this->belongsTo(CuttingJob::class, 'cutting_job_id');
    }

    public function finishedItem()
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function qcResults()
    {
        return $this->hasMany(QcResult::class, 'cutting_job_bundle_id');
    }

    public function wipWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'wip_warehouse_id');
    }

    // <<< scope readyForSewing kita benerin di step 4

    public function scopeReadyForSewing($query, ?int $warehouseId = null)
    {
        return $query
            ->whereIn('status', ['qc_ok', 'qc_mixed'])
            ->where('qty_qc_ok', '>', 0)
            ->where('wip_qty', '>', 0)
            ->when($warehouseId, function ($q) use ($warehouseId) {
                $q->where('wip_warehouse_id', $warehouseId);
            });
    }
}
